<?php

namespace App\Services\Activity;

use App\Enums\ActivityAction;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\AuthorizationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ActivityQueryService
{
    private const COLUMNS = ['id', 'actor_id', 'usuario_afectado_id', 'recurso_id', 'reserva_id', 'prestamo_id', 'incidencia_id', 'accion', 'fecha_hora', 'resultado', 'descripcion', 'proceso_responsable', 'critica'];

    private const LABELS = ['usuario_afectado_id' => 'Usuario afectado', 'recurso_id' => 'Recurso', 'reserva_id' => 'Reserva', 'prestamo_id' => 'Préstamo', 'incidencia_id' => 'Incidencia'];

    public function query(Usuario $actor, ActivityFilters $filters): Builder
    {
        Gate::forUser($actor->fresh())->authorize('actividad.ver');
        $query = DB::table('registros_actividad as a')->where('a.fecha_hora', '>=', $filters->start->toDateTimeString())->where('a.fecha_hora', '<', $filters->end->toDateTimeString());
        foreach (['actor_id', ...ActivityRecorder::CONTEXT, 'accion', 'resultado', 'critica', 'proceso_responsable'] as $key) {
            if (isset($filters->values[$key])) {
                $query->where('a.'.$key, $filters->values[$key]);
            }
        }
        $type = $filters->values['tipo_entidad'] ?? null;
        $field = ['USUARIO' => 'usuario_afectado_id', 'RECURSO' => 'recurso_id', 'RESERVA' => 'reserva_id', 'PRESTAMO' => 'prestamo_id', 'INCIDENCIA' => 'incidencia_id'][$type] ?? null;
        if ($field) {
            $query->whereNotNull('a.'.$field);
        } elseif ($type === 'SIN_REFERENCIA') {
            foreach (ActivityRecorder::CONTEXT as $key) {
                $query->whereNull('a.'.$key);
            }
        } elseif (in_array($type, ['ESPACIO', 'EQUIPAMIENTO'], true)) {
            $table = $type === 'ESPACIO' ? 'espacios_academicos' : 'equipamientos_tecnologicos';
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from($table.' as subtype')->whereColumn('subtype.recurso_id', 'a.recurso_id'));
        }
        if (($filters->values['responsable_tipo'] ?? null) === 'humano') {
            $query->whereNotNull('a.actor_id');
        }
        if (($filters->values['responsable_tipo'] ?? null) === 'proceso') {
            $query->whereNotNull('a.proceso_responsable');
        }

        return $query->orderByDesc('a.fecha_hora')->orderByDesc('a.id');
    }

    public function listing(Usuario $actor, ActivityFilters $filters): array
    {
        $query = $this->query($actor, $filters);
        $permissions = app(AuthorizationService::class)->permissions($actor->fresh());
        $this->projection($query, in_array('usuarios.ver', $permissions, true));
        $rows = $query->paginate(25, ['*'], 'page', (int) ($filters->values['page'] ?? 1))->appends($filters->values);
        $rows->through(fn ($row) => $this->dto($row));

        return ['registros' => $rows, 'filtros' => $filters->values,
            'acciones' => array_map(fn ($a) => ['value' => $a->value, 'label' => $a->label()], ActivityAction::cases()),
            'tipos_entidad' => ActivityFilters::ENTITIES, 'procesos' => ActivityRecorder::PROCESSES];
    }

    public function detail(Usuario $actor, int $id): array
    {
        Gate::forUser($actor->fresh())->authorize('actividad.ver');
        $permissions = app(AuthorizationService::class)->permissions($actor->fresh());
        $query = DB::table('registros_actividad as a')->where('a.id', $id);
        $this->projection($query, in_array('usuarios.ver', $permissions, true));
        $row = $query->first();
        abort_unless($row, 404);
        $dto = $this->dto($row);
        $gate = Gate::forUser($actor->fresh());
        foreach (['actor', 'usuario_afectado'] as $key) {
            if ($dto[$key] && in_array('usuarios.ver', $permissions, true) && Usuario::whereKey($dto[$key]['id'])->exists()) {
                $dto[$key]['url'] = route('usuarios.show', $dto[$key]['id'], false);
            }
        }
        foreach ($dto['referencias'] as &$ref) {
            [$class, $route] = match ($ref['campo']) {
                'reserva_id' => [Reserva::class, 'reservas.show'],
                'prestamo_id' => [Prestamo::class, 'prestamos.show'],
                'incidencia_id' => [IncidenciaMantenimiento::class, 'incidencias.show'],
                'recurso_id' => [EspacioAcademico::class, 'espacios.show'],
                default => [null, null],
            };
            if ($ref['campo'] === 'usuario_afectado_id') {
                $ref['url'] = $dto['usuario_afectado']['url'];
            }
            if ($class) {
                $model = $class::find($ref['id']);
                if (! $model && $ref['campo'] === 'recurso_id') {
                    $model = EquipamientoTecnologico::find($ref['id']);
                    $route = 'equipamientos.show';
                }
                if ($model && $gate->allows('view', $model)) {
                    $ref['url'] = route($route, $ref['id'], false);
                }
            }
        }
        unset($ref);
        $evidence = $row->critica ? DB::table('evidencias_trazabilidad')->where('registro_actividad_id', $id)->value('id') : null;
        $dto['evidencia'] = $row->critica ? ['existe' => $evidence !== null, 'url' => $evidence && in_array('trazabilidad.evidencias.ver', $permissions, true) ? route('trazabilidad.show', $evidence, false) : null] : null;

        return $dto;
    }

    private function projection(Builder $query, bool $names): void
    {
        $query->select(array_map(fn ($key) => 'a.'.$key, self::COLUMNS));
        if ($names) {
            $query->leftJoin('usuarios as actor', 'actor.id', '=', 'a.actor_id')->leftJoin('usuarios as affected', 'affected.id', '=', 'a.usuario_afectado_id');
            $query->addSelect(['actor.nombres as actor_nombres', 'actor.apellidos as actor_apellidos', 'affected.nombres as afectado_nombres', 'affected.apellidos as afectado_apellidos']);
        }
    }

    private function dto(object $row): array
    {
        $action = ActivityAction::tryFrom($row->accion);
        $user = static fn ($id, $first, $last) => $id ? ['id' => (int) $id, 'label' => $first !== null ? trim($first.' '.$last).' · Usuario #'.$id : 'Usuario #'.$id, 'nombre_actual' => $first !== null, 'url' => null] : null;
        $refs = [];
        foreach (self::LABELS as $field => $label) {
            if ($row->$field !== null) {
                $refs[] = ['campo' => $field, 'id' => (int) $row->$field, 'label' => $label.' #'.$row->$field, 'url' => null];
            }
        }

        return ['id' => (int) $row->id, 'fecha_hora' => CarbonImmutable::parse($row->fecha_hora, 'America/La_Paz')->toIso8601String(),
            'accion' => $action?->value, 'accion_label' => $action?->label() ?? 'Acción histórica no catalogada',
            'resultado' => $row->resultado, 'critica' => (bool) $row->critica,
            'actor' => $user($row->actor_id, $row->actor_nombres ?? null, $row->actor_apellidos ?? null),
            'usuario_afectado' => $user($row->usuario_afectado_id, $row->afectado_nombres ?? null, $row->afectado_apellidos ?? null),
            'proceso_responsable' => $row->proceso_responsable === null ? null : (in_array($row->proceso_responsable, ActivityRecorder::PROCESSES, true) ? $row->proceso_responsable : 'Proceso interno'),
            'referencias' => $refs, 'descripcion' => $action ? ActivityDescription::safe($action, $row->descripcion) : null];
    }
}
