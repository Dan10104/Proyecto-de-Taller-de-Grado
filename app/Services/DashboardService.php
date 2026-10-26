<?php

namespace App\Services;

use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\EvaluacionLiberacion;
use App\Models\IncidenciaMantenimiento;
use App\Models\Usuario;
use App\Services\Notifications\NotificationReadService;
use App\Services\Reporting\ReportingAccess;
use App\Services\Reporting\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const RESERVATIONS = ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA', 'LIBERADA', 'FINALIZADA'];

    private const LOANS = ['PENDIENTE', 'APROBADO', 'RECHAZADO', 'ENTREGADO', 'DEVUELTO'];

    public function read(Usuario $actor, array $filters): array
    {
        $access = ReportingAccess::for($actor);
        $access->require('dashboard.ver');
        $view = $filters['vista'] ?? ($access->administrative() ? 'administrativo' : 'personal');
        if (($view === 'administrativo' && ! $access->administrative()) || ($view === 'personal' && ! $access->personal())) {
            throw new AuthorizationException;
        }
        $now = CarbonImmutable::now('America/La_Paz')->startOfSecond();
        $period = ReportPeriod::from($filters, $now);
        $result = DB::transaction(fn () => $view === 'administrativo' ? $this->administrative($actor, $access, $period, $now) : $this->personal($actor, $access, $now));
        $access->revalidate($actor);

        return $result + ['vista' => $view, 'vistas' => array_values(array_filter([$access->administrative() ? 'administrativo' : null, $access->personal() ? 'personal' : null])), 'filtros' => $period->filters(), 'generado_en' => $now->toDateTimeString(), 'zona' => 'America/La_Paz'];
    }

    private function administrative(Usuario $actor, ReportingAccess $access, ReportPeriod $period, CarbonImmutable $now): array
    {
        $cards = [];
        $charts = [];
        if ($access->has('reservas.ver_administrativas')) {
            $q = app(ReservationQueryService::class)->scoped($actor, true);
            $cards[] = $this->card('A1', 'Reservas pendientes', (clone $q)->where('estado', 'PENDIENTE')->count());
            $cards[] = $this->card('A2', 'Reservas aprobadas activas', (clone $q)->where('estado', 'APROBADA')->where('inicio', '<=', $now)->where('fin', '>', $now)->count());
            if ($access->has('liberacion.evaluaciones.ver_administrativas')) {
                $n = EvaluacionLiberacion::whereIn('reserva_id', (clone $q)->select('id'))->where('resultado', 'LIBERAR')->where('fecha_hora', '>=', $period->start)->where('fecha_hora', '<', $period->end)->count();
                $cards[] = $this->card('A3', 'Liberaciones registradas', $n, 'Periodo seleccionado · evaluaciones LIBERAR');
            }
            $cohort = $this->period((clone $q), 'solicitada_en', $period);
            $charts[] = $this->chart('G1', 'Reservas por estado', $this->distribution($cohort, 'estado', self::RESERVATIONS), 'Solicitadas en el periodo · estado actual');
            $charts[] = $this->trend('G5', 'Solicitudes de reserva', $q, 'solicitada_en', $period);
        }
        if ($access->has('espacios.ver')) {
            $d = $this->distribution(EspacioAcademico::query(), 'estado', ['DISPONIBLE', 'BLOQUEADO', 'EN_MANTENIMIENTO']);
            $cards[] = $this->card('A4', 'Espacios por estado operativo', array_sum($d), 'Estado operativo actual · no es disponibilidad temporal', $d);
        }
        if ($access->has('prestamos.ver_administrativos')) {
            $q = app(LoanQueryService::class)->scoped($actor, true);
            $pending = (clone $q)->where('estado', 'PENDIENTE')->count();
            $delivered = (clone $q)->where('estado', 'ENTREGADO')->count();
            $overdue = (clone $q)->where('estado', 'ENTREGADO')->where('fin_previsto', '<', $now)->count();
            $cards[] = $this->card('A5', 'Préstamos pendientes', $pending);
            $cards[] = $this->card('A6', 'Préstamos entregados', $delivered, 'Estado actual', ['VIGENTES' => $delivered - $overdue, 'VENCIDOS' => $overdue]);
            $cards[] = $this->card('A7', 'Préstamos vencidos', $overdue);
            $charts[] = $this->chart('G2', 'Préstamos por estado', $this->distribution($this->period(clone $q, 'solicitado_en', $period), 'estado', self::LOANS), 'Solicitados en el periodo · estado actual');
            $charts[] = $this->trend('G6', 'Solicitudes de préstamo', $q, 'solicitado_en', $period);
        }
        if ($access->has('equipamientos.ver')) {
            $d = $this->distribution(EquipamientoTecnologico::query(), 'estado', ['DISPONIBLE', 'PRESTADO', 'EN_MANTENIMIENTO', 'BAJA']);
            $cards[] = $this->card('A8', 'Equipamiento por estado', array_sum($d), 'Estado actual', $d);
            $charts[] = $this->chart('G3', 'Equipamiento por estado', $d, 'Estado actual · independiente del periodo');
        }
        if ($access->has('incidencias.ver')) {
            $d = $this->distribution(IncidenciaMantenimiento::where('estado', 'ABIERTA'), 'tipo', ['INCIDENCIA', 'MANTENIMIENTO']);
            $cards[] = $this->card('A9', 'Incidencias y mantenimientos abiertos', array_sum($d), 'Estado actual', $d);
            $charts[] = $this->chart('G4', 'Incidencias abiertas por tipo', $d, 'Estado actual · independiente del periodo');
        }
        $iot = null;
        if ($access->has('iot.monitoreo.ver', 'espacios.ver')) {
            try {
                $iot = app(IotMonitoringService::class)->summary(EspacioAcademico::query(), $now);
                $cards[] = $this->card('A10', 'Monitoreo IoT no determinable', $iot['no_determinable'], 'Espacios monitorizados · no implica que estén libres');
            } catch (\Throwable $e) {
                report($e);
                $cards[] = $this->card('A10', 'Monitoreo IoT no determinable', null, 'No se pudo consultar el monitoreo');
                $iot = ['error' => true];
            }
        }
        usort($cards, fn ($a, $b) => strnatcmp($a['id'], $b['id']));
        usort($charts, fn ($a, $b) => strnatcmp($a['id'], $b['id']));

        return ['tarjetas' => $cards, 'graficos' => $charts, 'proximas' => [], 'iot' => $iot];
    }

    private function personal(Usuario $actor, ReportingAccess $access, CarbonImmutable $now): array
    {
        $cards = [];
        $next = [];
        if ($access->has('reservas.ver_propias')) {
            $q = app(ReservationQueryService::class)->scoped($actor, false);
            $cards[] = $this->card('P1', 'Mis reservas pendientes', (clone $q)->where('estado', 'PENDIENTE')->count());
            $upcoming = (clone $q)->where('estado', 'APROBADA')->where('inicio', '>=', $now);
            $cards[] = $this->card('P2', 'Mis próximas reservas aprobadas', (clone $upcoming)->count());
            $next = $upcoming->with('espacio.recurso')->orderBy('inicio')->orderBy('id')->limit(5)->get(['id', 'codigo', 'espacio_id', 'inicio', 'fin'])->map(fn ($r) => ['id' => $r->id, 'codigo' => $r->codigo, 'espacio' => $r->espacio->recurso->nombre, 'inicio' => $r->inicio->format('Y-m-d H:i'), 'fin' => $r->fin->format('Y-m-d H:i')])->all();
        }
        if ($access->has('prestamos.ver_propios')) {
            $q = app(LoanQueryService::class)->scoped($actor, false);
            foreach ([['P3', 'Mis préstamos pendientes', 'PENDIENTE'], ['P4', 'Mis préstamos por entregar', 'APROBADO'], ['P5', 'Mis préstamos entregados', 'ENTREGADO']] as [$id, $title, $state]) {
                $cards[] = $this->card($id, $title, (clone $q)->where('estado', $state)->count());
            }
            $cards[] = $this->card('P6', 'Mis préstamos vencidos', (clone $q)->where('estado', 'ENTREGADO')->where('fin_previsto', '<', $now)->count());
        }
        if ($access->has('notificaciones.ver_propias')) {
            $cards[] = $this->card('P7', 'Mis notificaciones no leídas', app(NotificationReadService::class)->unread($actor));
        }

        return ['tarjetas' => $cards, 'graficos' => [], 'proximas' => $next, 'iot' => null];
    }

    private function period($query, string $field, ReportPeriod $period)
    {
        return $query->where($field, '>=', $period->start)->where($field, '<', $period->end);
    }

    private function distribution($query, string $field, array $states): array
    {
        $values = array_fill_keys($states, 0);
        foreach ((clone $query)->select($field)->selectRaw('COUNT(*) as cantidad')->groupBy($field)->toBase()->get() as $row) {
            $values[$row->{$field}] = (int) $row->cantidad;
        }

        return $values;
    }

    private function trend(string $id, string $title, $query, string $field, ReportPeriod $period): array
    {
        // $field comes only from the two private call sites, never from a request.
        $format = $period->monthly() ? '%Y-%m' : '%Y-%m-%d';
        $buckets = $period->buckets();
        foreach ($this->period(clone $query, $field, $period)->selectRaw("DATE_FORMAT($field, ?) as bucket, COUNT(*) as cantidad", [$format])->groupBy('bucket')->orderBy('bucket')->toBase()->get() as $row) {
            $buckets[$row->bucket] = (int) $row->cantidad;
        }

        return $this->chart($id, $title, $buckets, ($period->monthly() ? 'Por mes' : 'Por día').' · solicitudes en el periodo', 'tendencia');
    }

    private function card(string $id, string $title, ?int $value, string $context = 'Estado actual', array $detail = []): array
    {
        return ['id' => $id, 'titulo' => $title, 'valor' => $value, 'contexto' => $context, 'desglose' => $detail];
    }

    private function chart(string $id, string $title, array $values, string $context, string $type = 'barras'): array
    {
        return ['id' => $id, 'titulo' => $title, 'tipo' => $type, 'contexto' => $context, 'valores' => $values, 'sin_datos' => array_sum($values) === 0];
    }
}
