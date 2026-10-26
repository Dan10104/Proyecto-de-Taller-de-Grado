<?php

namespace App\Services;

use App\Models\SensorIot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IotMonitoringService
{
    public function current(array $spaceIds, ?array $sensorIds = null): array
    {
        if ($spaceIds === []) {
            return [];
        }
        $now = CarbonImmutable::now('America/La_Paz')->startOfSecond();
        $query = $this->currentQuery($spaceIds, $sensorIds, $now);
        $result = array_fill_keys($spaceIds, []);
        foreach (DB::query()->fromSub($query, 'readings')->where('position', 1)->orderBy('sensor_id')->orderBy('tipo')->get() as $row) {
            $contradiction = $row->min_value !== $row->max_value || ($row->nonnull_count > 0 && $row->nonnull_count < $row->total_count);
            $result[$row->espacio_historico_id][] = [
                'sensor_id' => $row->sensor_id, 'tipo' => $row->tipo, 'valor' => $contradiction ? null : $row->valor,
                'fecha_evento' => $row->fecha_evento, 'fecha_recepcion' => $row->fecha_recepcion,
                'frescura' => 'ACTUAL', 'contradiccion' => $contradiction,
                'vigente_por_segundos' => config('iot.monitoring_freshness_seconds') - max($now->timestamp - CarbonImmutable::parse($row->fecha_evento, 'America/La_Paz')->timestamp, $now->timestamp - CarbonImmutable::parse($row->fecha_recepcion, 'America/La_Paz')->timestamp),
            ];
        }

        return $result;
    }

    private function currentQuery($spaceIds, ?array $sensorIds, CarbonImmutable $now): \Illuminate\Database\Query\Builder
    {
        $from = $now->copy()->subSeconds(config('iot.monitoring_freshness_seconds'));
        $partition = 'PARTITION BY e.sensor_id, e.tipo, e.fecha_evento';

        return DB::table('eventos_ocupacion as e')->join('sensores_iot as s', 's.id', '=', 'e.sensor_id')
            ->whereIn('e.espacio_historico_id', $spaceIds)->whereColumn('s.espacio_actual_id', 'e.espacio_historico_id')->where('s.estado', 'HABILITADO')
            ->when($sensorIds !== null, fn ($q) => $q->whereIn('e.sensor_id', $sensorIds))
            ->whereBetween('e.fecha_evento', [$from, $now])->whereBetween('e.fecha_recepcion', [$from, $now])
            ->select('e.*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY e.sensor_id, e.tipo ORDER BY e.fecha_evento DESC, e.fecha_recepcion DESC, e.id DESC) as position')
            ->selectRaw("MIN(e.valor) OVER ($partition) as min_value, MAX(e.valor) OVER ($partition) as max_value, COUNT(e.valor) OVER ($partition) as nonnull_count, COUNT(*) OVER ($partition) as total_count");
    }

    public function summary(Builder $spaces, CarbonImmutable $now): array
    {
        $monitored = (clone $spaces)->whereExists(function ($q) {
            $q->selectRaw('1')->from('sensores_iot as sensor')->whereColumn('sensor.espacio_actual_id', 'espacios_academicos.recurso_id')->where('sensor.estado', 'HABILITADO');
        });
        $count = (clone $monitored)->count();
        $ids = (clone $monitored)->select('espacios_academicos.recurso_id')->toBase();
        $current = $this->currentQuery($ids, null, $now);
        $recent = DB::query()->fromSub($current, 'readings')->where('position', 1)->distinct()->count('espacio_historico_id');

        // D-10 deliberately provides no universal interpretation of generic valor.
        return ['monitorizados' => $count, 'no_determinable' => $count, 'con_evidencia_reciente' => $recent, 'sin_evidencia_reciente' => $count - $recent];
    }

    public function availability(array $spaceIds): array
    {
        $result = [];
        try {
            $current = $this->current($spaceIds);
        } catch (\Throwable) {
            // Failure of the supplementary reader must not change CU06 results.
            $current = array_fill_keys($spaceIds, []);
        }
        foreach ($current as $id => $readings) {
            // CU06 never receives identifiers, internal sensor IDs or technical history.
            $result[$id] = ['ocupacion' => 'NO_DETERMINABLE', 'lecturas' => array_map(fn ($reading) => array_diff_key($reading, ['sensor_id' => true]), $readings)];
        }

        return $result;
    }

    public function communication(SensorIot $sensor): string
    {
        if ($sensor->ultima_comunicacion === null) {
            return 'SIN_COMUNICACION';
        }
        $age = now()->timestamp - $sensor->ultima_comunicacion->timestamp;

        return $age >= 0 && $age <= config('iot.monitoring_freshness_seconds') ? 'RECIENTE' : 'SIN_COMUNICACION_RECIENTE';
    }
}
