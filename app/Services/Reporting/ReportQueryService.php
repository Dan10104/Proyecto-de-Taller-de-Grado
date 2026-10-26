<?php

namespace App\Services\Reporting;

use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportQueryService
{
    public function prepare(Usuario $actor, string $id, array $input, bool $export = false): array
    {
        $access = ReportingAccess::for($actor);
        $definition = app(ReportRegistry::class)->get($id);
        app(ReportRegistry::class)->authorize($definition, $access, $export);
        $now = CarbonImmutable::now('America/La_Paz')->startOfSecond();
        $filters = app(ReportFilters::class)->normalize($definition, $actor, $input, $now);
        $columns = $definition->columns($access, $filters);
        $labels = $this->labels($definition, $actor, $filters, $now);
        $query = app(OperationalReportQuery::class)->build($definition, $actor, $access, $filters, $now);

        return compact('definition', 'access', 'now', 'filters', 'columns', 'labels', 'query');
    }

    public function preview(Usuario $actor, string $id, array $filters, int $page = 1, int $perPage = 25): array
    {
        $prepared = $this->prepare($actor, $id, $filters);
        $result = DB::transaction(function () use ($prepared, $page, $perPage) {
            $q = $prepared['query'];
            $total = DB::query()->fromSub((clone $q)->reorder(), 'report_rows')->count();
            $rows = (clone $q)->forPage($page, $perPage)->get()->map(fn ($r) => $this->project($r, $prepared['columns']))->all();

            return ['filas' => $rows, 'total' => $total, 'pagina' => $page, 'por_pagina' => $perPage, 'ultima_pagina' => max(1, (int) ceil($total / $perPage))];
        });
        $prepared['access']->revalidate($actor);

        return $result + $this->metadata($prepared) + ['firma_preview' => $this->fingerprint($prepared)];
    }

    public function materialize(array $prepared, Usuario $actor, string $directory, ReportBudget $budget): ReportDataset
    {
        $path = $directory.DIRECTORY_SEPARATOR.'dataset.jsonl';
        $file = fopen($path, 'xb');
        if (! $file) {
            throw new \RuntimeException('No se pudo preparar el reporte.');
        }
        try {
            $count = DB::transaction(function () use ($prepared, $budget, $path, $file) {
                $max = max(1, (int) config('reporting.max_rows'));
                $count = DB::query()->fromSub((clone $prepared['query'])->reorder()->limit($max + 1), 'limited_report')->count();
                if ($count === 0) {
                    throw ValidationException::withMessages(['exportacion' => 'Sin datos para los filtros seleccionados.']);
                }
                if ($count > $max) {
                    throw ValidationException::withMessages(['exportacion' => 'Demasiados resultados. El máximo es '.$max.' filas; refine los filtros.']);
                }
                $written = 0;
                // Fixed ordering + one RR snapshot: OFFSET batches cannot drift between commits.
                foreach ((clone $prepared['query'])->lazy((int) config('reporting.chunk_size', 250)) as $row) {
                    $line = json_encode($this->project($row, $prepared['columns']), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
                    if (fwrite($file, $line) !== strlen($line)) {
                        throw new \RuntimeException('No se pudo completar el conjunto de datos.');
                    }
                    $written++;
                    $budget->check($path);
                }
                if ($written !== $count) {
                    throw new \RuntimeException('El conjunto de datos no es consistente.');
                }

                return $count;
            });
        } finally {
            fclose($file);
        }
        $metadata = $this->metadata($prepared) + ['generador' => $actor->nombres.' '.$actor->apellidos];

        return new ReportDataset($metadata, $path, $count);
    }

    public function metadata(array $prepared): array
    {
        return ['reporte' => $prepared['definition']->id, 'titulo' => $prepared['definition']->title, 'columnas' => $prepared['columns'], 'filtros' => $prepared['filters'], 'filtros_visibles' => $prepared['labels'], 'generado_en' => $prepared['now']->toDateTimeString(), 'zona' => 'America/La_Paz'];
    }

    public function fingerprint(array $prepared): string
    {
        return hash('sha256', json_encode([$prepared['definition']->id, $prepared['filters'], $prepared['columns'], $prepared['labels']['Periodo'] ?? null, $prepared['access']->signature()], JSON_THROW_ON_ERROR));
    }

    public function project(object $row, array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            $value = $row->{$column['key']} ?? null;
            $result[$column['key']] = $value === null ? null : match ($column['type']) {
                'boolean' => (bool) $value, 'integer' => (int) $value, 'decimal' => (float) $value, default => (string) $value,
            };
        }

        return $result;
    }

    private function labels(ReportDefinition $d, Usuario $actor, array $filters, CarbonImmutable $now): array
    {
        $labels = [];
        if ($d->dated) {
            $p = ReportPeriod::from($filters, $now);
            $labels['Periodo'] = $p->start->format('d/m/Y').' — '.$p->end->subDay()->format('d/m/Y');
            $labels['Criterio temporal'] = match ($d->id) {
                'reservas' => 'Solapamiento del intervalo de uso', 'prestamos' => 'Solapamiento del intervalo previsto', 'incidencias' => 'Fecha de inicio', 'iot' => 'Fecha del evento', default => 'Fecha de la actividad'
            };
        }
        $names = ['estado' => 'Estado', 'espacio_id' => 'Espacio', 'piso_id' => 'Piso', 'solicitante_id' => 'Solicitante', 'equipamiento_id' => 'Equipamiento', 'tipo_espacio_id' => 'Tipo de espacio', 'tipo_equipamiento_id' => 'Tipo de equipamiento', 'espacio_base_id' => 'Ubicación base', 'sin_ubicacion' => 'Sin ubicación base', 'recurso_id' => 'Recurso', 'espacio_historico_id' => 'Espacio histórico', 'sensor_id' => 'Sensor', 'tipo' => 'Tipo', 'vencido' => 'Vencido', 'disponible' => 'Disponible para el periodo', 'vista' => 'Vista', 'inicio' => 'Inicio consultado', 'fin' => 'Fin consultado', 'categoria' => 'Categoría', 'accion' => 'Acción', 'resultado' => 'Resultado'];
        foreach ($d->filters as $key => $spec) {
            if (! isset($filters[$key])) {
                continue;
            }
            $value = $filters[$key];
            if ($spec === 'reference') {
                $value = app(ReportReferenceService::class)->query($d, $actor, $key)->where('option_id', $value)->value('option_label');
            }
            if ($spec === 'instant') {
                $value = CarbonImmutable::parse($value, 'America/La_Paz')->format('d/m/Y H:i');
            }
            $labels[$names[$key]] = (string) $value;
        }

        return $labels;
    }
}
