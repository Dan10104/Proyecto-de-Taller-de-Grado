<?php

namespace App\Services\Notifications;

use App\Models\EvaluacionLiberacion;
use App\Models\IncidenciaMantenimiento;
use App\Models\RegistroActividad;
use Illuminate\Support\Facades\DB;

class NotificationActivityRecorder
{
    public function reservation(int $id, string $action): RegistroActividad
    {
        $r = DB::table('reservas')->find($id);

        return $this->record(['accion' => $action, 'reserva_id' => $r->id, 'recurso_id' => $r->espacio_id, 'usuario_afectado_id' => $r->solicitante_id,
            'actor_id' => $action === 'RESERVA_SOLICITADA' ? $r->solicitante_id : $r->resuelta_por_id,
            'fecha_hora' => $action === 'RESERVA_SOLICITADA' ? $r->solicitada_en : $r->resuelta_en]);
    }

    public function loan(int $id, string $action, ?int $actor): RegistroActividad
    {
        $p = DB::table('prestamos')->find($id);

        return $this->record(['accion' => $action, 'prestamo_id' => $p->id, 'recurso_id' => $p->equipamiento_id, 'usuario_afectado_id' => $p->solicitante_id,
            'actor_id' => $actor, 'proceso_responsable' => $action === 'PRESTAMO_VENCIDO' ? 'scheduler:vencimientos' : null]);
    }

    public function incident(IncidenciaMantenimiento $incident, bool $closed): RegistroActividad
    {
        $p = $incident->devolucion_id ? DB::table('devoluciones_prestamo as d')->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')->where('d.id', $incident->devolucion_id)->first(['p.id', 'p.solicitante_id']) : null;

        return $this->record(['accion' => $incident->tipo->value.($closed ? '_CERRADO' : '_REGISTRADO'),
            'actor_id' => $closed ? $incident->cerrado_por_id : $incident->reportado_por_id,
            'incidencia_id' => $incident->id, 'recurso_id' => $incident->recurso_id, 'prestamo_id' => $p?->id, 'usuario_afectado_id' => $p?->solicitante_id]);
    }

    public function release(EvaluacionLiberacion $e): RegistroActividad
    {
        $r = DB::table('reservas')->find($e->reserva_id);

        return $this->record(['accion' => $e->resultado->value === 'ALERTAR' ? 'RESERVA_ALERTADA_LIBERACION' : 'RESERVA_LIBERADA',
            'actor_id' => null, 'proceso_responsable' => 'scheduler:liberacion', 'reserva_id' => $r->id, 'recurso_id' => $r->espacio_id,
            'usuario_afectado_id' => $r->solicitante_id, 'fecha_hora' => $e->fecha_hora,
            'descripcion' => 'Evaluación CU23 #'.$e->id.'; política #'.$e->politica_id.'.']);
    }

    private function record(array $attributes): RegistroActividad
    {
        // INCIDENCIA uses feminine suffixes; MANTENIMIENTO uses masculine suffixes.
        $attributes['accion'] = str_replace(['INCIDENCIA_REGISTRADO', 'INCIDENCIA_CERRADO'], ['INCIDENCIA_REGISTRADA', 'INCIDENCIA_CERRADA'], $attributes['accion']);
        if (DB::transactionLevel() < 1 || ! isset(NotificationRuleRegistry::RULES[$attributes['accion']]) || in_array($attributes['accion'], ['RESERVA_APROBADA', 'RESERVA_CANCELADA', 'PRESTAMO_ENTREGADO', 'PRESTAMO_DEVUELTO'], true)) {
            throw new \LogicException('La nueva actividad notificable requiere el hito no crítico en su transacción.');
        }
        $a = new RegistroActividad;
        $a->forceFill($attributes + ['actor_id' => null, 'usuario_afectado_id' => null, 'recurso_id' => null, 'reserva_id' => null, 'prestamo_id' => null, 'incidencia_id' => null,
            'descripcion' => null, 'proceso_responsable' => null, 'critica' => false, 'resultado' => 'EXITO', 'fecha_hora' => now()])->save();

        return $a;
    }
}
