<?php

namespace App\Services\Notifications;

use App\Models\RegistroActividad;
use Illuminate\Support\Facades\DB;

class NotificationContext
{
    public function resolve(RegistroActividad $a): ?array
    {
        $critical = in_array($a->accion, ['RESERVA_APROBADA', 'RESERVA_CANCELADA', 'PRESTAMO_ENTREGADO', 'PRESTAMO_DEVUELTO'], true);
        $process = match ($a->accion) {
            'PRESTAMO_VENCIDO' => 'scheduler:vencimientos',
            'RESERVA_ALERTADA_LIBERACION', 'RESERVA_LIBERADA' => 'scheduler:liberacion',
            default => null,
        };
        if ($a->critica !== $critical || ($process === null ? ($a->actor_id === null || $a->proceso_responsable !== null) : ($a->actor_id !== null || $a->proceso_responsable !== $process))) {
            return null;
        }
        $context = ['{fecha_evento}' => $a->fecha_hora->format('Y-m-d H:i:s')];
        if (str_starts_with($a->accion, 'RESERVA_')) {
            $r = DB::table('reservas')->find($a->reserva_id);
            if (! $r || $a->prestamo_id !== null || $a->incidencia_id !== null || $r->espacio_id !== $a->recurso_id || $r->solicitante_id !== $a->usuario_afectado_id) {
                return null;
            }
            if ($process !== null) {
                if (! preg_match('/^Evaluación CU23 #(\d+); política #(\d+)\.$/Du', $a->descripcion ?? '', $ids)) {
                    return null;
                }
                $e = DB::table('evaluaciones_liberacion')->find((int) $ids[1]);
                if (! $e || $e->reserva_id !== $r->id || $e->politica_id !== (int) $ids[2] || $e->resultado !== ($a->accion === 'RESERVA_LIBERADA' ? 'LIBERAR' : 'ALERTAR') || $e->fecha_hora !== $a->fecha_hora->toDateTimeString()) {
                    return null;
                }
            }

            return $context + ['{reserva}' => $r->codigo];
        }
        if (str_starts_with($a->accion, 'INCIDENCIA_') || str_starts_with($a->accion, 'MANTENIMIENTO_')) {
            $i = DB::table('incidencias_mantenimiento')->find($a->incidencia_id);
            if (! $i || $a->reserva_id !== null || $i->recurso_id !== $a->recurso_id || ! str_starts_with($a->accion, $i->tipo.'_')) {
                return null;
            }
            if ($i->devolucion_id === null) {
                return $a->prestamo_id === null && $a->usuario_afectado_id === null ? $context + ['{incidencia}' => (string) $i->id] : null;
            }
            $d = DB::table('devoluciones_prestamo')->find($i->devolucion_id);
            if (! $d || $d->prestamo_id !== $a->prestamo_id) {
                return null;
            }
            $context['{incidencia}'] = (string) $i->id;
        } elseif ($a->incidencia_id !== null || $a->reserva_id !== null) {
            return null;
        }
        $p = DB::table('prestamos')->find($a->prestamo_id);
        if (! $p || $p->equipamiento_id !== $a->recurso_id || $p->solicitante_id !== $a->usuario_afectado_id) {
            return null;
        }

        return $context + ['{prestamo}' => $p->codigo];
    }
}
