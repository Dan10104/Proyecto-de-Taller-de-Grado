<?php

namespace Tests\Feature;

use App\Exceptions\EquipmentConflictException;
use App\Exceptions\ReservationConflictException;
use App\Jobs\GenerateNotifications;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\EvidenciaTrazabilidad;
use App\Models\IncidenciaMantenimiento;
use App\Models\Notificacion;
use App\Models\Prestamo;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Services\IncidentMaintenanceService;
use App\Services\LoanService;
use App\Services\Notifications\NotificationGenerationService;
use App\Services\Notifications\NotificationLink;
use App\Services\Notifications\OverdueLoanService;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\NotificationTestCase;

#[Group('notifications')]
class NotificationBusinessTest extends NotificationTestCase
{
    private function beforeStart(): void
    {
        $this->travelTo(CarbonImmutable::parse('2090-01-10 07:00:00', 'America/La_Paz'));
    }

    private function delivered(): Prestamo
    {
        $a = $this->user('Administrador');
        $p = Prestamo::factory()->create(['solicitante_id' => $this->user('Docente')->id]);
        app(LoanService::class)->approve($a, $p);
        app(LoanService::class)->deliver($a, $p, ['condicion_entrega' => 'Completo', 'equipo_verificado' => true, 'receptor_verificado' => true]);

        return $p;
    }

    private function flushNotices(): void
    {
        foreach (RegistroActividad::all() as $a) {
            $this->generate($a);
        }
    }

    public function test_reservation_request_decision_and_cancellation_keep_one_activity_per_event(): void
    {
        $this->beforeStart();
        $s = $this->user('Docente');
        $a = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        $service = app(ReservationService::class);
        $r = $service->create($s, ['espacio_id' => $space->recurso_id, 'inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00', 'proposito' => 'Prueba']);
        $this->assertDatabaseHas('registros_actividad', ['accion' => 'RESERVA_SOLICITADA', 'reserva_id' => $r->id, 'actor_id' => $s->id, 'critica' => 0, 'resultado' => 'EXITO']);
        $service->approve($a, $r);
        $service->cancel($s, $r);
        $this->assertSame(['RESERVA_SOLICITADA', 'RESERVA_APROBADA', 'RESERVA_CANCELADA'], RegistroActividad::orderBy('id')->pluck('accion')->all());
        $this->assertDatabaseCount('evidencias_trazabilidad', 2);
        $this->flushNotices();
        $this->assertDatabaseCount('notificaciones', 3);
        try {
            $service->cancel($s, $r->fresh());
            $this->fail();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(ReservationConflictException::class, $e);
        }
        $this->assertDatabaseCount('registros_actividad', 3);
    }

    public function test_rejections_are_successful_noncritical_events_and_do_not_copy_reason(): void
    {
        $this->beforeStart();
        $a = $this->user('Administrador');
        $r = Reserva::factory()->create();
        $p = Prestamo::factory()->create();
        app(ReservationService::class)->reject($a, $r, 'Motivo privado sintético');
        app(LoanService::class)->reject($a, $p, 'Motivo privado sintético');
        $this->flushNotices();
        $this->assertDatabaseCount('notificaciones', 2);
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
        foreach (RegistroActividad::all() as $event) {
            $this->assertFalse($event->critica);
            $this->assertSame('EXITO', $event->resultado->value);
            $this->assertNull($event->descripcion);
        }
        foreach (Notificacion::all() as $n) {
            $this->assertStringNotContainsString('Motivo privado', $n->mensaje);
        }
    }

    public function test_loan_request_review_and_final_return_reuse_the_critical_milestones(): void
    {
        $this->beforeStart();
        $s = $this->user('Docente');
        $a = $this->user('Administrador');
        $other = $this->user('Encargado de Laboratorio');
        $e = EquipamientoTecnologico::factory()->create();
        $service = app(LoanService::class);
        $p = $service->create($s, ['equipamiento_id' => $e->recurso_id, 'inicio_previsto' => '2090-01-10T08:00', 'fin_previsto' => '2090-01-10T09:00', 'motivo' => 'Solicitud sintética']);
        $service->approve($a, $p);
        $service->deliver($a, $p, ['condicion_entrega' => 'Completo', 'equipo_verificado' => true, 'receptor_verificado' => true]);
        $service->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisar']);
        $this->assertDatabaseMissing('registros_actividad', ['accion' => 'PRESTAMO_DEVUELTO']);
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->flushNotices();
        $review = RegistroActividad::where('accion', 'DEVOLUCION_PENDIENTE_REVISION')->sole();
        $this->assertFalse($review->critica);
        $this->assertEquals([$other->id], $review->notificaciones->pluck('destinatario_id')->all());
        $service->confirmReview($other, $p, ['condicion' => 'ADECUADA', 'apto' => true]);
        $this->flushNotices();
        $this->assertSame(['PRESTAMO_SOLICITADO', 'PRESTAMO_APROBADO', 'PRESTAMO_ENTREGADO', 'DEVOLUCION_PENDIENTE_REVISION', 'PRESTAMO_DEVUELTO'], RegistroActividad::orderBy('id')->pluck('accion')->all());
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
        $this->assertDatabaseCount('evidencias_trazabilidad', 2);
        $last = RegistroActividad::where('accion', 'PRESTAMO_DEVUELTO')->sole();
        $this->assertSame($other->id, $last->actor_id);
        $this->assertSame($s->id, $last->notificaciones->sole()->destinatario_id);
    }

    public static function incidentKinds(): array
    {
        return [['INCIDENCIA', 'INCIDENCIA_REGISTRADA', 'INCIDENCIA_CERRADA'], ['MANTENIMIENTO', 'MANTENIMIENTO_REGISTRADO', 'MANTENIMIENTO_CERRADO']];
    }

    #[DataProvider('incidentKinds')]
    public function test_manual_incident_and_maintenance_open_close_have_no_invented_subject(string $kind, string $opened, string $closed): void
    {
        $a = $this->user('Administrador');
        $other = $this->user('Encargado de Laboratorio');
        $e = EquipamientoTecnologico::factory()->create();
        $service = app(IncidentMaintenanceService::class);
        $i = $service->open($a, ['recurso_id' => $e->recurso_id, 'tipo' => $kind, 'descripcion' => 'Condición sintética', 'inicio' => '2090-01-10T08:00']);
        $service->close($a, $i, ['solucion' => 'Revisión completada', 'apto' => true]);
        $this->assertSame([$opened, $closed], RegistroActividad::orderBy('id')->pluck('accion')->all());
        $this->flushNotices();
        foreach (RegistroActividad::all() as $event) {
            $this->assertNull($event->usuario_afectado_id);
            $this->assertNull($event->prestamo_id);
            $this->assertFalse($event->critica);
        }
        $this->assertDatabaseCount('notificaciones', 2);
        $this->assertEquals([$other->id], Notificacion::distinct()->pluck('destinatario_id')->all());
        try {
            $service->close($a, $i, ['solucion' => 'Otra']);
            $this->fail();
        } catch (EquipmentConflictException) {
            $this->assertDatabaseCount('registros_actividad', 2);
        }
    }

    public function test_return_incident_affects_only_linked_borrower_and_has_authorized_loan_link(): void
    {
        $p = $this->delivered();
        $a = $this->user('Administrador');
        app(LoanService::class)->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Condición privada sintética']);
        $i = IncidenciaMantenimiento::sole();
        $event = RegistroActividad::where('accion', 'INCIDENCIA_REGISTRADA')->sole();
        $this->assertSame($p->id, $event->prestamo_id);
        $this->assertSame($p->solicitante_id, $event->usuario_afectado_id);
        $this->generate($event);
        $n = Notificacion::where('registro_actividad_id', $event->id)->where('destinatario_id', $p->solicitante_id)->sole();
        $this->assertStringContainsString('requiere revisión', $n->mensaje);
        $this->assertStringNotContainsString('privada', $n->mensaje);
        $this->assertSame('/prestamos/'.$p->id, app(NotificationLink::class)->resolve($n, $p->solicitante)['url']);
        app(IncidentMaintenanceService::class)->close($a, $i, ['solucion' => 'Inspección sintética', 'apto' => true]);
        $closed = RegistroActividad::where('accion', 'INCIDENCIA_CERRADA')->sole();
        $this->generate($closed);
        $this->assertTrue($closed->notificaciones()->where('destinatario_id', $p->solicitante_id)->exists());
    }

    public function test_overdue_strict_boundary_single_activity_and_return_does_not_rewrite_history(): void
    {
        $p = $this->delivered();
        $service = app(OverdueLoanService::class);
        $this->assertFalse($service->detect($p->id));
        $this->travelTo(CarbonImmutable::parse('2090-01-10 09:00:00', 'America/La_Paz'));
        $this->assertFalse($service->detect($p->id));
        $this->travel(1)->seconds();
        $this->assertTrue($service->detect($p->id));
        $this->assertFalse($service->detect($p->id));
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->fresh()->estado->value);
        $a = RegistroActividad::where('accion', 'PRESTAMO_VENCIDO')->sole();
        $this->assertNull($a->actor_id);
        $this->assertSame('scheduler:vencimientos', $a->proceso_responsable);
        app(LoanService::class)->receive($this->user('Administrador'), $p, ['condicion' => 'ADECUADA']);
        $this->assertFalse($service->detect($p->id));
        $this->assertGreaterThan(0, $this->generate($a));
        $this->assertStringContainsString('fue detectado como vencido', Notificacion::where('registro_actividad_id', $a->id)->first()->mensaje);
        $this->artisan('prestamos:detectar-vencimientos')->assertSuccessful();
        $this->assertSame(1, RegistroActividad::where('accion', 'PRESTAMO_VENCIDO')->count());
        $pending = Prestamo::factory()->create(['fin_previsto' => '2090-01-10 09:00:00']);
        $this->assertFalse($service->detect($pending->id));
    }

    public static function decisions(): array
    {
        return [['ALERTA', false, false, 'ALERTAR', 'RESERVA_ALERTADA_LIBERACION'], ['AUTOMATICA', false, false, 'LIBERAR', 'RESERVA_LIBERADA'], ['AUTOMATICA', true, false, 'MANTENER_POR_USO', null], ['AUTOMATICA', false, true, 'NO_DETERMINABLE', null]];
    }

    #[DataProvider('decisions')]
    public function test_release_actions_match_committed_evaluation_and_terminal_retries(string $mode, bool $qr, bool $iot, string $result, ?string $action): void
    {
        $r = $this->reservation(['modo' => $mode, 'usa_iot' => $iot, 'max_antiguedad_lectura_min' => $iot ? 5 : null]);
        if ($qr) {
            $this->qr($r);
        }
        $this->user('Administrador');
        $e = $this->evaluate($r);
        $this->assertSame($result, $e->resultado->value);
        $this->assertNull($this->evaluate($r));
        if ($action === null) {
            $this->assertDatabaseCount('registros_actividad', 0);
            $this->assertDatabaseCount('notificaciones', 0);

            return;
        }
        $a = RegistroActividad::sole();
        $this->assertSame($action, $a->accion);
        $this->assertFalse($a->critica);
        $this->assertNull($a->actor_id);
        $this->assertSame('Evaluación CU23 #'.$e->id.'; política #'.$e->politica_id.'.', $a->descripcion);
        $this->assertSame($result === 'LIBERAR' ? 'LIBERADA' : 'APROBADA', $r->fresh()->estado->value);
        $this->assertSame(2, $this->generate($a));
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
    }

    public function test_activity_failure_rolls_back_request_and_postcommit_callback(): void
    {
        $this->beforeStart();
        $u = $this->user('Docente');
        $space = EspacioAcademico::factory()->create();
        $armed = true;
        DB::listen(function ($q) use (&$armed) {
            if ($armed && str_contains($q->sql, 'insert into `registros_actividad`')) {
                $armed = false;
                throw new \RuntimeException('activity-failure');
            }
        });
        try {
            app(ReservationService::class)->create($u, ['espacio_id' => $space->recurso_id, 'inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00', 'proposito' => 'Prueba']);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('activity-failure', $e->getMessage());
        }
        $this->assertDatabaseCount('reservas', 0);
        $this->assertDatabaseCount('registros_actividad', 0);
        Bus::assertNotDispatched(GenerateNotifications::class);
    }

    public function test_dispatch_only_after_commit_and_notification_failure_preserves_business_and_evidence(): void
    {
        $this->beforeStart();
        $r = Reserva::factory()->create();
        $admin = $this->user('Administrador');
        DB::beginTransaction();
        app(ReservationService::class)->approve($admin, $r);
        Bus::assertNotDispatched(GenerateNotifications::class);
        $this->assertDatabaseCount('notificaciones', 0);
        DB::commit();
        $a = RegistroActividad::sole();
        Bus::assertDispatched(GenerateNotifications::class, fn ($job) => $job->activityId === $a->id);
        $mock = \Mockery::mock(NotificationGenerationService::class);
        $mock->shouldReceive('generate')->once()->andThrow(new \RuntimeException('synthetic delivery failure'));
        (new GenerateNotifications($a->id))->handle($mock);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertDatabaseCount('registros_actividad', 1);
        $this->assertDatabaseCount('evidencias_trazabilidad', 1);
        $this->assertSame(1, $this->generate($a));
        $this->assertSame('PENDIENTE', EvidenciaTrazabilidad::sole()->estado->value);
    }

    public function test_local_evidence_failure_does_not_prevent_notification_dispatch(): void
    {
        $this->beforeStart();
        $r = Reserva::factory()->create();
        $admin = $this->user('Administrador');
        $armed = true;
        DB::listen(function ($q) use (&$armed) {
            if ($armed && str_contains($q->sql, 'insert into `evidencias_trazabilidad`')) {
                $armed = false;
                throw new \RuntimeException('synthetic evidence failure');
            }
        });
        app(ReservationService::class)->approve($admin, $r);
        $a = RegistroActividad::sole();
        Bus::assertDispatched(GenerateNotifications::class);
        $this->assertSame(1, $this->generate($a));
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
    }

    public static function failureFamilies(): array
    {
        return [['reservation'], ['loan'], ['return'], ['incident'], ['release']];
    }

    #[DataProvider('failureFamilies')]
    public function test_postcommit_generation_failure_cannot_undo_each_business_family(string $family): void
    {
        $a = $this->user('Administrador');
        if ($family === 'release') {
            $r = $this->reservation(['modo' => 'AUTOMATICA']);
            $this->evaluate($r);
            $state = fn () => $r->fresh()->estado->value;
            $expected = 'LIBERADA';
        } elseif ($family === 'incident') {
            $this->user('Encargado de Laboratorio');
            $e = EquipamientoTecnologico::factory()->create();
            $i = app(IncidentMaintenanceService::class)->open($a, ['recurso_id' => $e->recurso_id, 'tipo' => 'INCIDENCIA', 'descripcion' => 'Prueba', 'inicio' => '2090-01-10T08:00']);
            $state = fn () => $i->fresh()->estado->value;
            $expected = 'ABIERTA';
        } elseif ($family === 'return') {
            $p = $this->delivered();
            app(LoanService::class)->receive($a, $p, ['condicion' => 'ADECUADA']);
            $state = fn () => $p->fresh()->estado->value;
            $expected = 'DEVUELTO';
        } elseif ($family === 'loan') {
            $this->beforeStart();
            $p = Prestamo::factory()->create();
            app(LoanService::class)->approve($a, $p);
            $state = fn () => $p->fresh()->estado->value;
            $expected = 'APROBADO';
        } else {
            $this->beforeStart();
            $r = Reserva::factory()->create();
            app(ReservationService::class)->approve($a, $r);
            $state = fn () => $r->fresh()->estado->value;
            $expected = 'APROBADA';
        }
        $events = RegistroActividad::orderBy('id')->get();
        $this->assertNotEmpty($events);
        $before = DB::table('registros_actividad')->orderBy('id')->get()->toJson();
        $mock = \Mockery::mock(NotificationGenerationService::class);
        $mock->shouldReceive('generate')->times($events->count())->andThrow(new \RuntimeException('synthetic-consumer-failure'));
        foreach ($events as $event) {
            (new GenerateNotifications($event->id))->handle($mock);
        }
        $this->assertSame($expected, $state());
        $this->assertSame($before, DB::table('registros_actividad')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('notificaciones', 0);
        $this->flushNotices();
        $this->assertGreaterThan(0, Notificacion::count());
    }

    public function test_release_activity_failure_rolls_back_transition_evaluation_and_evidence_pivots(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $this->qr($r, ['resultado' => 'INVALIDA']);
        $armed = true;
        DB::listen(function ($q) use (&$armed) {
            if ($armed && str_starts_with($q->sql, 'insert into `registros_actividad`')) {
                $armed = false;
                throw new \RuntimeException('release-event-failure');
            }
        });
        try {
            $this->evaluate($r);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('release-event-failure', $e->getMessage());
        }
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertDatabaseCount('evaluaciones_liberacion', 0);
        $this->assertDatabaseCount('evaluaciones_validaciones_qr', 0);
        $this->assertDatabaseCount('registros_actividad', 0);
        Bus::assertNotDispatched(GenerateNotifications::class);
    }
}
