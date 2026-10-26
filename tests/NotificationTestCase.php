<?php

namespace Tests;

use App\Jobs\GenerateNotifications;
use App\Models\DevolucionPrestamo;
use App\Models\EvaluacionLiberacion;
use App\Models\IncidenciaMantenimiento;
use App\Models\PoliticaLiberacion;
use App\Models\Prestamo;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\Notifications\NotificationGenerationService;
use Illuminate\Support\Facades\Bus;

abstract class NotificationTestCase extends ReleaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([GenerateNotifications::class]);
        config(['notifications.events_from' => '2000-01-01 00:00:00']);
    }

    protected function activity(string $action = 'RESERVA_APROBADA', ?Usuario $subject = null, ?Usuario $actor = null, array $extra = []): RegistroActividad
    {
        $subject ??= $this->user('Docente');
        $actor ??= $this->user('Administrador');
        $data = ['accion' => $action, 'actor_id' => $actor->id, 'usuario_afectado_id' => $subject->id, 'recurso_id' => null, 'reserva_id' => null, 'prestamo_id' => null, 'incidencia_id' => null,
            'resultado' => 'EXITO', 'critica' => in_array($action, ['RESERVA_APROBADA', 'RESERVA_CANCELADA', 'PRESTAMO_ENTREGADO', 'PRESTAMO_DEVUELTO']), 'fecha_hora' => now(), 'descripcion' => null, 'proceso_responsable' => null];
        if (str_starts_with($action, 'RESERVA_')) {
            $r = Reserva::factory()->create(['solicitante_id' => $subject->id]);
            $data = array_replace($data, ['reserva_id' => $r->id, 'recurso_id' => $r->espacio_id]);
            if ($action === 'RESERVA_SOLICITADA') {
                $data['actor_id'] = $subject->id;
            }
            if (in_array($action, ['RESERVA_ALERTADA_LIBERACION', 'RESERVA_LIBERADA'])) {
                $e = new EvaluacionLiberacion;
                $e->forceFill(['reserva_id' => $r->id, 'politica_id' => PoliticaLiberacion::factory()->create()->id, 'fecha_hora' => now(), 'resultado' => $action === 'RESERVA_LIBERADA' ? 'LIBERAR' : 'ALERTAR', 'motivo' => 'Decisión sintética'])->save();
                $data = array_replace($data, ['actor_id' => null, 'proceso_responsable' => 'scheduler:liberacion', 'descripcion' => 'Evaluación CU23 #'.$e->id.'; política #'.$e->politica_id.'.']);
            }
        } else {
            $p = Prestamo::factory()->create(['solicitante_id' => $subject->id]);
            $data = array_replace($data, ['prestamo_id' => $p->id, 'recurso_id' => $p->equipamiento_id]);
            if ($action === 'PRESTAMO_SOLICITADO') {
                $data['actor_id'] = $subject->id;
            }
            if ($action === 'PRESTAMO_VENCIDO') {
                $data = array_replace($data, ['actor_id' => null, 'proceso_responsable' => 'scheduler:vencimientos']);
            }
            if (str_starts_with($action, 'INCIDENCIA_') || str_starts_with($action, 'MANTENIMIENTO_')) {
                $d = DevolucionPrestamo::factory()->create(['prestamo_id' => $p->id]);
                $i = IncidenciaMantenimiento::factory()->create(['recurso_id' => $p->equipamiento_id, 'devolucion_id' => $d->id, 'reportado_por_id' => $actor->id, 'tipo' => str_starts_with($action, 'INCIDENCIA_') ? 'INCIDENCIA' : 'MANTENIMIENTO']);
                $data['incidencia_id'] = $i->id;
            }
        }

        return RegistroActividad::withoutEvents(function () use ($data, $extra) {
            $a = new RegistroActividad;
            $a->forceFill(array_replace($data, $extra))->save();

            return $a;
        });
    }

    protected function generate(RegistroActividad $a): int
    {
        return app(NotificationGenerationService::class)->generate($a);
    }
}
