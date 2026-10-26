<?php

namespace App\Services\Reporting;

use App\Models\Usuario;
use App\Services\LoanQueryService;
use App\Services\ReservationQueryService;
use App\Services\SpaceAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperationalReportQuery
{
    public function build(ReportDefinition $d, Usuario $actor, ReportingAccess $access, array $filters, CarbonImmutable $now): Builder
    {
        $period = $d->dated ? ReportPeriod::from($filters, $now) : null;

        return match ($d->id) {
            'reservas' => $this->reservations($actor, $filters, $period),
            'espacios' => $this->spaces($filters),
            'equipamiento' => $this->equipment($filters, $access),
            'prestamos' => $this->loans($actor, $filters, $period, $now),
            'incidencias' => $this->incidents($filters, $period),
            'iot' => $this->iot($filters, $period),
            'actividad-operacional' => app(ActivityReportQuery::class)->build($actor, $access, $filters, $period),
        };
    }

    private function filters(Builder $q, array $filters, array $map): Builder
    {
        foreach ($map as $key => $column) {
            if (isset($filters[$key])) {
                $q->where($column, $filters[$key]);
            }
        }

        return $q;
    }

    private function reservations(Usuario $actor, array $f, ReportPeriod $p): Builder
    {
        $scope = app(ReservationQueryService::class)->scoped($actor, true)->select('id');
        $q = DB::table('reservas as x')->join('espacios_academicos as s', 's.recurso_id', '=', 'x.espacio_id')->join('recursos_institucionales as r', 'r.id', '=', 's.recurso_id')->join('usuarios as u', 'u.id', '=', 'x.solicitante_id')
            ->whereIn('x.id', $scope)->where('x.inicio', '<', $p->end)->where('x.fin', '>', $p->start)
            ->select('x.id', 'x.codigo', 'x.inicio', 'x.fin', 'x.estado', 'x.solicitada_en', 'x.resuelta_en', 'x.cancelada_en')
            ->selectRaw("CONCAT(r.codigo, ' · ', r.nombre) as espacio, CONCAT(u.nombres, ' ', u.apellidos) as solicitante");

        return $this->filters($q, $f, ['estado' => 'x.estado', 'espacio_id' => 'x.espacio_id', 'piso_id' => 's.piso_id', 'solicitante_id' => 'x.solicitante_id'])->orderBy('x.inicio')->orderBy('x.id');
    }

    private function spaces(array $f): Builder
    {
        $q = DB::table('espacios_academicos as s')->join('recursos_institucionales as r', 'r.id', '=', 's.recurso_id')->join('pisos as p', 'p.id', '=', 's.piso_id')->join('tipos_espacio as t', 't.id', '=', 's.tipo_espacio_id')
            ->select('s.recurso_id as id', 'r.codigo', 'r.nombre', 'p.nombre as piso', 't.nombre as tipo', 's.capacidad', 's.estado');
        if (($f['vista'] ?? 'inventario') === 'disponibilidad') {
            $available = app(SpaceAvailabilityService::class)->candidates([], CarbonImmutable::parse($f['inicio'], 'America/La_Paz'), CarbonImmutable::parse($f['fin'], 'America/La_Paz'))->select('espacios_academicos.recurso_id')->toBase();
            $correlated = (clone $available)->whereColumn('espacios_academicos.recurso_id', 's.recurso_id');
            $q->selectRaw('EXISTS ('.$correlated->toSql().') as disponible', $correlated->getBindings());
            if (isset($f['disponible'])) {
                $q->whereIn('s.recurso_id', $available, 'and', $f['disponible'] === 'no');
            }
        }

        return $this->filters($q, $f, ['piso_id' => 's.piso_id', 'tipo_espacio_id' => 's.tipo_espacio_id', 'estado' => 's.estado'])->orderBy('r.codigo')->orderBy('s.recurso_id');
    }

    private function equipment(array $f, ReportingAccess $access): Builder
    {
        $q = DB::table('equipamientos_tecnologicos as e')->join('recursos_institucionales as r', 'r.id', '=', 'e.recurso_id')->join('tipos_equipamiento as t', 't.id', '=', 'e.tipo_equipamiento_id')->leftJoin('recursos_institucionales as base', 'base.id', '=', 'e.espacio_base_id')
            ->select('e.recurso_id as id', 'r.codigo', 'r.nombre', 't.nombre as tipo', 'e.marca', 'e.modelo', 'e.estado')->selectRaw("COALESCE(CONCAT(base.codigo, ' · ', base.nombre), 'Sin ubicación base') as ubicacion_base");
        if ($access->has('equipamientos.gestionar')) {
            $q->addSelect('e.numero_serie');
        }
        if (isset($f['sin_ubicacion'])) {
            $q->whereNull('e.espacio_base_id');
        }

        return $this->filters($q, $f, ['estado' => 'e.estado', 'tipo_equipamiento_id' => 'e.tipo_equipamiento_id', 'espacio_base_id' => 'e.espacio_base_id'])->orderBy('r.codigo')->orderBy('e.recurso_id');
    }

    private function loans(Usuario $actor, array $f, ReportPeriod $p, CarbonImmutable $now): Builder
    {
        $scope = app(LoanQueryService::class)->scoped($actor, true)->select('id');
        $q = DB::table('prestamos as x')->join('recursos_institucionales as r', 'r.id', '=', 'x.equipamiento_id')->join('usuarios as u', 'u.id', '=', 'x.solicitante_id')
            ->whereIn('x.id', $scope)->where('x.inicio_previsto', '<', $p->end)->where('x.fin_previsto', '>', $p->start)
            ->select('x.id', 'x.codigo', 'x.inicio_previsto', 'x.fin_previsto', 'x.estado', 'x.solicitado_en', 'x.resuelto_en')
            ->selectRaw("CONCAT(r.codigo, ' · ', r.nombre) as equipamiento, CONCAT(u.nombres, ' ', u.apellidos) as solicitante")
            ->selectRaw("(x.estado = 'ENTREGADO' AND x.fin_previsto < ?) as vencido", [$now->toDateTimeString()]);
        if (isset($f['vencido'])) {
            $q->whereRaw("(x.estado = 'ENTREGADO' AND x.fin_previsto < ?) = ?", [$now->toDateTimeString(), $f['vencido'] === 'si' ? 1 : 0]);
        }

        return $this->filters($q, $f, ['estado' => 'x.estado', 'equipamiento_id' => 'x.equipamiento_id', 'solicitante_id' => 'x.solicitante_id'])->orderBy('x.inicio_previsto')->orderBy('x.id');
    }

    private function incidents(array $f, ReportPeriod $p): Builder
    {
        $q = DB::table('incidencias_mantenimiento as i')->join('recursos_institucionales as r', 'r.id', '=', 'i.recurso_id')->join('usuarios as u', 'u.id', '=', 'i.reportado_por_id')
            ->where('i.inicio', '>=', $p->start)->where('i.inicio', '<', $p->end)->select('i.id', 'i.tipo', 'i.inicio', 'i.fin_previsto', 'i.estado', 'i.cerrada_en')
            ->selectRaw("CONCAT(r.codigo, ' · ', r.nombre) as recurso, CONCAT(u.nombres, ' ', u.apellidos) as reportante");

        return $this->filters($q, $f, ['tipo' => 'i.tipo', 'estado' => 'i.estado', 'recurso_id' => 'i.recurso_id'])->orderByDesc('i.inicio')->orderByDesc('i.id');
    }

    private function iot(array $f, ReportPeriod $p): Builder
    {
        $q = DB::table('eventos_ocupacion as e')->join('recursos_institucionales as r', 'r.id', '=', 'e.espacio_historico_id')->join('sensores_iot as s', 's.id', '=', 'e.sensor_id')
            ->where('e.fecha_evento', '>=', $p->start)->where('e.fecha_evento', '<', $p->end)->select('e.id', 's.identificador as sensor', 'e.tipo', 'e.valor', 'e.fecha_evento', 'e.fecha_recepcion')
            ->selectRaw("CONCAT(r.codigo, ' · ', r.nombre) as espacio_historico");

        return $this->filters($q, $f, ['espacio_historico_id' => 'e.espacio_historico_id', 'sensor_id' => 'e.sensor_id', 'tipo' => 'e.tipo'])->orderByDesc('e.fecha_evento')->orderByDesc('e.fecha_recepcion')->orderByDesc('e.id');
    }
}
