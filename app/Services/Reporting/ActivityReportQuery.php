<?php

namespace App\Services\Reporting;

use App\Models\Usuario;
use App\Services\LoanQueryService;
use App\Services\ReservationQueryService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ActivityReportQuery
{
    public const FAMILIES = [
        'RESERVAS' => ['permissions' => ['reservas.ver_administrativas'], 'actions' => ['RESERVA_SOLICITADA', 'RESERVA_APROBADA', 'RESERVA_RECHAZADA', 'RESERVA_CANCELADA']],
        'PRESTAMOS' => ['permissions' => ['prestamos.ver_administrativos'], 'actions' => ['PRESTAMO_SOLICITADO', 'PRESTAMO_APROBADO', 'PRESTAMO_RECHAZADO', 'PRESTAMO_ENTREGADO', 'DEVOLUCION_PENDIENTE_REVISION', 'PRESTAMO_DEVUELTO', 'PRESTAMO_VENCIDO']],
        'INCIDENCIAS' => ['permissions' => ['incidencias.ver'], 'actions' => ['INCIDENCIA_REGISTRADA', 'INCIDENCIA_CERRADA', 'MANTENIMIENTO_REGISTRADO', 'MANTENIMIENTO_CERRADO']],
        'LIBERACION' => ['permissions' => ['reservas.ver_administrativas', 'liberacion.evaluaciones.ver_administrativas'], 'actions' => ['RESERVA_ALERTADA_LIBERACION', 'RESERVA_LIBERADA']],
        'REPORTES' => ['permissions' => ['reportes.ver'], 'actions' => ['REPORTE_EXPORTADO']],
    ];

    public static function allowed(ReportingAccess $access): array
    {
        return array_filter(self::FAMILIES, fn ($f) => $access->has(...$f['permissions']));
    }

    public function build(Usuario $actor, ReportingAccess $access, array $filters, ReportPeriod $period): Builder
    {
        $q = DB::table('registros_actividad as a')->leftJoin('usuarios as u', 'u.id', '=', 'a.actor_id')
            ->leftJoin('reservas as r', 'r.id', '=', 'a.reserva_id')->leftJoin('prestamos as p', 'p.id', '=', 'a.prestamo_id')->leftJoin('incidencias_mantenimiento as i', 'i.id', '=', 'a.incidencia_id')
            ->where('a.fecha_hora', '>=', $period->start)->where('a.fecha_hora', '<', $period->end)
            // Same producer context invariants as NotificationContext, applied before SQL aggregation.
            ->whereRaw("a.critica = (a.accion IN ('RESERVA_APROBADA', 'RESERVA_CANCELADA', 'PRESTAMO_ENTREGADO', 'PRESTAMO_DEVUELTO'))")
            ->where(function ($context) {
                $context->where(function ($human) {
                    $human->whereNotNull('a.actor_id')->whereNull('a.proceso_responsable')->whereNotIn('a.accion', ['PRESTAMO_VENCIDO', 'RESERVA_ALERTADA_LIBERACION', 'RESERVA_LIBERADA']);
                })
                    ->orWhere(function ($process) {
                        $process->whereNull('a.actor_id')->whereRaw("a.proceso_responsable = CASE WHEN a.accion = 'PRESTAMO_VENCIDO' THEN 'scheduler:vencimientos' WHEN a.accion IN ('RESERVA_ALERTADA_LIBERACION', 'RESERVA_LIBERADA') THEN 'scheduler:liberacion' END");
                    });
            });
        $allowed = self::allowed($access);
        $q->where(function ($outer) use ($allowed, $actor) {
            foreach ($allowed as $category => $family) {
                $outer->orWhere(function ($part) use ($category, $family, $actor) {
                    $part->whereIn('a.accion', $family['actions']);
                    if ($category === 'REPORTES') {
                        $part->where('a.actor_id', $actor->id)->where('a.critica', false)->whereNull('a.proceso_responsable')->whereNull('a.reserva_id')->whereNull('a.prestamo_id')->whereNull('a.incidencia_id')->whereNull('a.recurso_id')->whereNull('a.usuario_afectado_id');
                    } elseif ($category === 'RESERVAS' || $category === 'LIBERACION') {
                        $part->whereIn('a.reserva_id', app(ReservationQueryService::class)->scoped($actor, true)->select('id'))->whereColumn('r.espacio_id', 'a.recurso_id')->whereColumn('r.solicitante_id', 'a.usuario_afectado_id')->whereNull('a.prestamo_id')->whereNull('a.incidencia_id');
                        if ($category === 'LIBERACION') {
                            $part->whereNull('a.actor_id')->where('a.proceso_responsable', 'scheduler:liberacion')->whereExists(function ($e) {
                                $e->selectRaw('1')->from('evaluaciones_liberacion as evaluation')->whereColumn('evaluation.reserva_id', 'a.reserva_id')->whereColumn('evaluation.fecha_hora', 'a.fecha_hora')
                                    ->whereRaw("a.descripcion = CONCAT('Evaluación CU23 #', evaluation.id, '; política #', evaluation.politica_id, '.')")
                                    ->whereRaw("evaluation.resultado = CASE WHEN a.accion = 'RESERVA_LIBERADA' THEN 'LIBERAR' ELSE 'ALERTAR' END");
                            });
                        }
                    } elseif ($category === 'PRESTAMOS') {
                        $part->whereIn('a.prestamo_id', app(LoanQueryService::class)->scoped($actor, true)->select('id'))->whereColumn('p.equipamiento_id', 'a.recurso_id')->whereColumn('p.solicitante_id', 'a.usuario_afectado_id')->whereNull('a.reserva_id')->whereNull('a.incidencia_id');
                    } else {
                        $part->whereNotNull('i.id')->whereColumn('i.recurso_id', 'a.recurso_id')->whereNull('a.reserva_id')
                            ->whereRaw("i.tipo = CASE WHEN a.accion IN ('MANTENIMIENTO_REGISTRADO', 'MANTENIMIENTO_CERRADO') THEN 'MANTENIMIENTO' ELSE 'INCIDENCIA' END")
                            ->where(function ($link) {
                                $link->where(function ($standalone) {
                                    $standalone->whereNull('i.devolucion_id')->whereNull('a.prestamo_id')->whereNull('a.usuario_afectado_id');
                                })
                                    ->orWhere(function ($returned) {
                                        $returned->whereColumn('p.equipamiento_id', 'a.recurso_id')->whereColumn('p.solicitante_id', 'a.usuario_afectado_id')->whereExists(function ($d) {
                                            $d->selectRaw('1')->from('devoluciones_prestamo as d')->whereColumn('d.id', 'i.devolucion_id')->whereColumn('d.prestamo_id', 'a.prestamo_id');
                                        });
                                    });
                            });
                    }
                });
            }
        });
        $case = 'CASE';
        $bindings = [];
        foreach (self::FAMILIES as $category => $family) {
            $case .= ' WHEN a.accion IN ('.implode(',', array_fill(0, count($family['actions']), '?')).') THEN ?';
            $bindings = [...$bindings, ...$family['actions'], $category];
        }
        $case .= ' END';
        $person = $access->has('usuarios.ver') ? "CONCAT(u.nombres, ' ', u.apellidos, ' (#', a.actor_id, ')')" : "CONCAT('Usuario #', a.actor_id)";
        $responsible = "CASE WHEN a.actor_id IS NOT NULL THEN $person WHEN a.proceso_responsable = 'scheduler:liberacion' THEN 'Liberación automática' WHEN a.proceso_responsable = 'scheduler:vencimientos' THEN 'Detección de vencimientos' ELSE 'Proceso interno' END";
        $ref = "CASE WHEN a.incidencia_id IS NOT NULL THEN CONCAT('Incidencia #', a.incidencia_id) WHEN a.prestamo_id IS NOT NULL THEN p.codigo WHEN a.reserva_id IS NOT NULL THEN r.codigo ELSE '—' END";
        $q->selectRaw('DATE(a.fecha_hora) as fecha, a.accion, a.resultado, a.actor_id as responsable_id')
            ->selectRaw("CASE WHEN a.proceso_responsable IN ('scheduler:liberacion', 'scheduler:vencimientos') THEN a.proceso_responsable ELSE '' END as proceso")
            ->selectRaw('COALESCE(a.incidencia_id, a.prestamo_id, a.reserva_id, 0) as referencia_id')->selectRaw($case.' as categoria', $bindings)->selectRaw($responsible.' as responsable')->selectRaw($ref.' as referencia')->selectRaw('COUNT(*) as cantidad')
            ->groupBy('fecha', 'a.accion', 'a.resultado', 'a.actor_id', 'proceso', 'categoria', 'responsable', 'referencia', 'referencia_id');
        if (isset($filters['categoria'])) {
            $q->having('categoria', $filters['categoria']);
        }
        foreach (['accion', 'resultado'] as $field) {
            if (isset($filters[$field])) {
                $q->where('a.'.$field, $filters[$field]);
            }
        }

        return $q->orderBy('fecha')->orderBy('categoria')->orderBy('a.accion')->orderBy('a.resultado')->orderByRaw('a.actor_id IS NULL')->orderBy('a.actor_id')->orderBy('proceso')->orderBy('referencia_id');
    }
}
