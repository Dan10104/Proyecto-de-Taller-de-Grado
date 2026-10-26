<?php

namespace Tests\Feature;

use App\Jobs\GenerateNotifications;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\EvidenciaTrazabilidad;
use App\Models\Notificacion;
use App\Models\RegistroActividad;
use App\Models\Usuario;
use App\Services\DashboardService;
use App\Services\EquipmentAvailabilityService;
use App\Services\LoanService;
use App\Services\Notifications\NotificationGenerationService;
use App\Services\Notifications\OverdueLoanService;
use App\Services\Reporting\ReportQueryService;
use App\Services\ReservationService;
use App\Services\SpaceAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Tests\NotificationTestCase;

class SystemCrossModuleTest extends NotificationTestCase
{
    private function cards(Usuario $actor): array
    {
        return array_column(app(DashboardService::class)->read($actor, [])['tarjetas'], 'valor', 'id');
    }

    private function consume(): void
    {
        // Execute the actual post-commit consumers as a worker would; replay also tests idempotence.
        foreach (Bus::dispatched(GenerateNotifications::class) as $job) {
            $job->handle(app(NotificationGenerationService::class));
            $job->handle(app(NotificationGenerationService::class));
        }
    }

    public function test_reservation_approval_commits_activity_evidence_notification_dashboard_and_report_together(): void
    {
        $this->travelTo(CarbonImmutable::parse('2090-01-10 07:00:00', 'America/La_Paz'));
        $admin = $this->user('Administrador');
        $owner = $this->user('Docente');
        $other = $this->user('Estudiante');
        $space = EspacioAcademico::factory()->create();
        $r = app(ReservationService::class)->create($owner, ['espacio_id' => $space->getKey(), 'inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00', 'proposito' => 'Integración sintética']);
        $this->assertSame(1, $this->cards($admin)['A1']);
        $this->assertSame(1, $this->cards($owner)['P1']);
        app(ReservationService::class)->approve($admin, $r);
        $this->consume();
        $event = RegistroActividad::where('accion', 'RESERVA_APROBADA')->sole();
        $this->assertSame($r->id, $event->reserva_id);
        $this->assertTrue($event->critica);
        $this->assertSame($event->id, EvidenciaTrazabilidad::sole()->registro_actividad_id);
        $this->assertSame($owner->id, Notificacion::where('registro_actividad_id', $event->id)->sole()->destinatario_id);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertSame(0, $this->cards($admin)['A1']);
        $this->assertSame(0, $this->cards($admin)['A2']);
        $this->assertSame(1, $this->cards($owner)['P2']);
        $this->assertSame(0, $this->cards($other)['P2']);
        $preview = app(ReportQueryService::class)->preview($admin, 'reservas', []);
        $this->assertSame('APROBADA', $preview['filas'][0]['estado']);
        $this->travelTo(CarbonImmutable::parse('2090-01-10 08:00:00', 'America/La_Paz'));
        $this->assertSame(1, $this->cards($admin)['A2']);
        $this->assertFalse(app(SpaceAvailabilityService::class)->candidates([], now()->toImmutable(), now()->addHour()->toImmutable())->whereKey($space->getKey())->exists());
        $this->travelTo(CarbonImmutable::parse('2090-01-10 09:00:00', 'America/La_Paz'));
        $this->assertSame(0, $this->cards($admin)['A2']);
        $this->assertTrue(app(SpaceAvailabilityService::class)->candidates([], now()->toImmutable(), now()->addHour()->toImmutable())->whereKey($space->getKey())->exists());
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
    }

    public function test_delivery_overdue_and_return_propagate_without_stale_dashboard_or_report_state(): void
    {
        $this->travelTo(CarbonImmutable::parse('2090-01-10 07:00:00', 'America/La_Paz'));
        $admin = $this->user('Administrador');
        $owner = $this->user('Estudiante');
        $equipment = EquipamientoTecnologico::factory()->create();
        $service = app(LoanService::class);
        $loan = $service->create($owner, ['equipamiento_id' => $equipment->getKey(), 'inicio_previsto' => '2090-01-10T08:00', 'fin_previsto' => '2090-01-10T09:00', 'motivo' => 'Integración sintética']);
        $service->approve($admin, $loan);
        $service->deliver($admin, $loan, ['condicion_entrega' => 'Completo', 'equipo_verificado' => true, 'receptor_verificado' => true]);
        $this->consume();
        $this->assertSame('PRESTADO', $equipment->fresh()->estado->value);
        $this->assertSame(1, $this->cards($admin)['A6']);
        $this->assertSame(1, $this->cards($owner)['P5']);
        $this->travelTo(CarbonImmutable::parse('2090-01-10 09:00:00', 'America/La_Paz'));
        $this->assertSame(0, $this->cards($owner)['P6']);
        $this->travel(1)->seconds();
        $this->assertTrue(app(OverdueLoanService::class)->detect($loan->id));
        $this->assertFalse(app(OverdueLoanService::class)->detect($loan->id));
        $this->consume();
        $this->assertSame(1, $this->cards($owner)['P6']);
        $service->receive($admin, $loan, ['condicion' => 'ADECUADA']);
        $this->consume();
        $this->assertSame('DEVUELTO', $loan->fresh()->estado->value);
        $this->assertSame('DISPONIBLE', $equipment->fresh()->estado->value);
        $this->assertSame(0, $this->cards($owner)['P5']);
        $this->assertSame(0, $this->cards($owner)['P6']);
        $this->assertSame(0, $this->cards($admin)['A6']);
        $this->assertSame(0, $this->cards($admin)['A7']);
        $this->assertDatabaseCount('evidencias_trazabilidad', 2);
        foreach (['PRESTAMO_ENTREGADO', 'PRESTAMO_DEVUELTO'] as $action) {
            $event = RegistroActividad::where('accion', $action)->sole();
            $this->assertTrue($event->critica);
            $this->assertSame($owner->id, Notificacion::where('registro_actividad_id', $event->id)->sole()->destinatario_id);
        }
        $preview = app(ReportQueryService::class)->preview($admin, 'prestamos', []);
        $this->assertSame('DEVUELTO', $preview['filas'][0]['estado']);
        $this->assertFalse($preview['filas'][0]['vencido']);
        $this->assertTrue(app(EquipmentAvailabilityService::class)->candidates([], now()->toImmutable(), now()->addHour()->toImmutable())->whereKey($equipment->getKey())->exists());
    }

    public function test_release_keeps_its_evidence_links_and_updates_availability_notice_activity_and_dashboard(): void
    {
        $admin = $this->user('Administrador');
        $owner = $this->user('Docente');
        $r = $this->reservation(['modo' => 'AUTOMATICA', 'usa_iot' => true, 'max_antiguedad_lectura_min' => 5], ['solicitante_id' => $owner->id]);
        $qr = $this->qr($r, ['resultado' => 'INVALIDA']);
        $event = $this->event($this->sensor($r));
        $this->assertSame(1, $this->cards($admin)['A2']);
        $evaluation = $this->evaluate($r);
        $this->consume();
        $this->assertSame('LIBERAR', $evaluation->resultado->value);
        $this->assertSame('LIBERADA', $r->fresh()->estado->value);
        $this->assertSame($qr->id, $evaluation->validaciones()->sole()->id);
        $this->assertSame($event->id, $evaluation->eventos()->sole()->id);
        $activity = RegistroActividad::where('accion', 'RESERVA_LIBERADA')->sole();
        $this->assertFalse($activity->critica);
        $this->assertSame('scheduler:liberacion', $activity->proceso_responsable);
        $this->assertTrue(Notificacion::where('registro_actividad_id', $activity->id)->where('destinatario_id', $owner->id)->exists());
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
        $this->assertSame(0, $this->cards($admin)['A2']);
        $this->assertSame(1, $this->cards($admin)['A3']);
        $this->assertTrue(app(SpaceAvailabilityService::class)->candidates([], now()->toImmutable(), now()->addMinutes(15)->toImmutable())->whereKey($r->espacio_id)->exists());
        $before = Notificacion::count();
        $this->assertNull($this->evaluate($r));
        $this->consume();
        $this->assertSame($before, Notificacion::count());
        $this->assertSame(1, RegistroActividad::where('accion', 'RESERVA_LIBERADA')->count());
    }
}
