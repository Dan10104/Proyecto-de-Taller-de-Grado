<?php

namespace App\Services\Traceability;

use App\Jobs\RegisterTraceabilityEvidence;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CriticalActivityRecorder
{
    public function reservation(int $id, bool $approval): void
    {
        $r = DB::table('reservas')->find($id);
        $this->record(['actor_id' => $approval ? $r->resuelta_por_id : $r->cancelada_por_id, 'usuario_afectado_id' => $r->solicitante_id, 'recurso_id' => $r->espacio_id, 'reserva_id' => $r->id, 'prestamo_id' => null,
            'accion' => $approval ? 'RESERVA_APROBADA' : 'RESERVA_CANCELADA', 'fecha_hora' => $approval ? $r->resuelta_en : $r->cancelada_en]);
    }

    public function loan(int $id, bool $delivery): void
    {
        $p = DB::table('prestamos')->find($id);
        $h = DB::table($delivery ? 'entregas_prestamo' : 'devoluciones_prestamo')->where('prestamo_id', $id)->first();
        $this->record(['actor_id' => $delivery ? $h->entregado_por_id : $h->confirmado_por_id, 'usuario_afectado_id' => $p->solicitante_id, 'recurso_id' => $p->equipamiento_id, 'reserva_id' => null, 'prestamo_id' => $p->id,
            'accion' => $delivery ? 'PRESTAMO_ENTREGADO' : 'PRESTAMO_DEVUELTO', 'fecha_hora' => $delivery ? $h->fecha_hora : $h->confirmada_en]);
    }

    private function record(array $attributes): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('La actividad requiere la transacción del hito.');
        }
        $activity = app(ActivityRecorder::class)->record($attributes + ['incidencia_id' => null, 'descripcion' => null, 'proceso_responsable' => null, 'critica' => true, 'resultado' => 'EXITO']);
        $id = (int) $activity->id;
        DB::afterCommit(static function () use ($id) {
            try {
                $evidence = app(LocalEvidenceService::class)->ensure($id);
                RegisterTraceabilityEvidence::dispatch($evidence->id)->onConnection('deferred');
            } catch (\Throwable) {
                // The durable activity is the recovery cursor. Do not undo a committed operation.
                Log::warning('traceability.local_pending', ['activity_id' => $id]);
            }
        });
    }
}
