<?php

namespace Tests\Feature;

use App\Exceptions\ReservationConflictException;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Permiso;
use App\Models\Reserva;
use App\Models\Rol;
use App\Services\IncidentMaintenanceService;
use App\Services\ReservationService;
use App\Services\SpaceAvailabilityService;
use App\Services\SpaceManagementService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\LoanTestCase;

class IncidentMaintenanceTest extends LoanTestCase
{
    public function test_both_resource_types_and_optional_period_have_manual_close_only(): void
    {
        $a = $this->user('Administrador');
        $s = app(IncidentMaintenanceService::class);
        foreach ([EquipamientoTecnologico::factory()->create(), EspacioAcademico::factory()->create()] as $resource) {
            foreach (['INCIDENCIA', 'MANTENIMIENTO'] as $kind) {
                $i = $s->open($a, $this->incidentData($resource->getKey(), ['tipo' => $kind, 'fin_previsto' => '2026-01-01T09:00']));
                $this->assertSame($kind, $i->tipo->value);
                $this->assertSame('ABIERTA', $i->estado->value);
                $this->assertSame($a->id, $i->reportado_por_id);
                $this->assertNull($i->devolucion_id);
                $this->assertSame('EN_MANTENIMIENTO', $resource->fresh()->estado->value);
                $s->close($a, $i, ['solucion' => 'Verificado y reparado']);
                $this->assertSame('CERRADA', $i->fresh()->estado->value);
                $this->assertSame($a->id, $i->fresh()->cerrado_por_id);
                $this->assertSame('EN_MANTENIMIENTO', $resource->fresh()->estado->value);
            }
        }
    }

    public function test_multiple_restrictions_close_independently_and_explicit_recovery_only_after_last(): void
    {
        $a = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $s = app(IncidentMaintenanceService::class);
        $i = $s->open($a, $this->incidentData($e->getKey()));
        $j = $s->open($a, $this->incidentData($e->getKey(), ['tipo' => 'MANTENIMIENTO']));
        $s->close($a, $i, ['solucion' => 'Primera condición resuelta', 'apto' => true]);
        $this->assertSame('CERRADA', $i->fresh()->estado->value);
        $this->assertSame('EN_MANTENIMIENTO', $e->fresh()->estado->value);
        $s->close($a, $j, ['solucion' => 'Segunda condición resuelta', 'apto' => true]);
        $this->assertSame('DISPONIBLE', $e->fresh()->estado->value);
        $this->conflict(fn () => $s->close($a, $j, ['solucion' => 'No sobrescribir', 'apto' => true]));
        $this->assertSame('Segunda condición resuelta', $j->fresh()->solucion);
    }

    public function test_close_retains_delivered_custody_baja_and_pending_review(): void
    {
        $a = $this->user('Administrador');
        $s = app(IncidentMaintenanceService::class);
        $p = $this->delivered($a);
        $i = $s->open($a, $this->incidentData($p->equipamiento_id));
        $s->close($a, $i, ['solucion' => 'Reparado', 'apto' => true]);
        $this->assertSame('PRESTADO', $p->equipamiento->fresh()->estado->value);
        $i = $s->open($a, $this->incidentData($p->equipamiento_id));
        $this->loans()->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Inspección pendiente']);
        $s->close($a, $i, ['solucion' => 'Condición resuelta', 'apto' => true]);
        $this->assertSame('EN_MANTENIMIENTO', $p->equipamiento->fresh()->estado->value);
        $e = EquipamientoTecnologico::factory()->create(['estado' => 'BAJA']);
        $i = $s->open($a, $this->incidentData($e->getKey()));
        $s->close($a, $i, ['solucion' => 'Cierre conservando baja', 'apto' => true]);
        $this->assertSame('BAJA', $e->fresh()->estado->value);
    }

    public function test_space_availability_and_existing_reservation_approval_respect_incidents(): void
    {
        $a = $this->user('Administrador');
        $d = $this->user('Docente');
        $space = EspacioAcademico::factory()->create();
        $r = Reserva::factory()->create(['espacio_id' => $space->getKey(), 'solicitante_id' => $d->id]);
        $service = app(IncidentMaintenanceService::class);
        $incident = $service->open($a, $this->incidentData($space->getKey(), ['estado_espacio' => 'BLOQUEADO']));
        $this->assertSame('BLOQUEADO', $space->fresh()->estado->value);
        $space->update(['estado' => 'DISPONIBLE']); // Defensive query must honor the independent restriction.
        $start = CarbonImmutable::parse('2090-01-10 08:00');
        $end = $start->addHour();
        $this->assertFalse(app(SpaceAvailabilityService::class)->candidates([], $start, $end)->whereKey($space->getKey())->exists());
        try {
            app(ReservationService::class)->approve($a, $r);
            $this->fail();
        } catch (ReservationConflictException) {
            $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
        }
        $this->conflict(fn () => app(SpaceManagementService::class)->state($a, $space, 'DISPONIBLE'));
        $service->close($a, $incident, ['solucion' => 'Reparado', 'apto' => true]);
        $this->assertTrue(app(SpaceAvailabilityService::class)->candidates([], $start, $end)->whereKey($space->getKey())->exists());
        app(ReservationService::class)->approve($a, $r);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
    }

    public function test_space_recovery_respects_active_blocks_and_keeps_future_commitments(): void
    {
        $a = $this->user('Administrador');
        $s = EspacioAcademico::factory()->create();
        $service = app(IncidentMaintenanceService::class);
        $block = $this->blockFor($s, now()->subHour()->format('Y-m-d H:i:s'), now()->addHour()->format('Y-m-d H:i:s'), true, $a);
        $future = $this->blockFor($s, '2090-01-10 08:00', '2090-01-10 09:00', true, $a);
        $r = Reserva::factory()->create(['espacio_id' => $s->getKey(), 'estado' => 'APROBADA']);
        $i = $service->open($a, $this->incidentData($s->getKey()));
        $service->close($a, $i, ['solucion' => 'Incidencia cerrada', 'apto' => true]);
        $this->assertSame('EN_MANTENIMIENTO', $s->fresh()->estado->value);
        $this->conflict(fn () => app(SpaceManagementService::class)->state($a, $s, 'DISPONIBLE'));
        app(SpaceManagementService::class)->deactivate($a, $s, $block->id);
        app(SpaceManagementService::class)->state($a, $s, 'DISPONIBLE');
        $this->assertTrue($future->fresh()->activo);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertFalse(app(SpaceAvailabilityService::class)->candidates([], CarbonImmutable::parse('2090-01-10 08:00'), CarbonImmutable::parse('2090-01-10 09:00'))->whereKey($s->getKey())->exists());
    }

    public function test_period_and_close_validation_and_no_actor_spoofing(): void
    {
        $a = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $service = app(IncidentMaintenanceService::class);
        foreach ([['descripcion' => ' '], ['fin_previsto' => '2026-01-01T08:00'], ['devolucion_id' => 1], ['reportado_por_id' => 1]] as $bad) {
            try {
                $service->open($a, $this->incidentData($e->getKey(), $bad));
                $this->fail();
            } catch (ValidationException) {
                $this->assertDatabaseCount('incidencias_mantenimiento', 0);
            }
        }
        $future = $service->open($a, $this->incidentData($e->getKey(), ['inicio' => '2090-01-01T08:00']));
        $this->conflict(fn () => $service->close($a, $future, ['solucion' => 'Todavía no comenzó']));
        $this->assertSame('ABIERTA', $future->fresh()->estado->value);
        $i = $service->open($a, $this->incidentData($e->getKey()));
        try {
            $service->close($a, $i, ['solucion' => ' ']);
            $this->fail();
        } catch (ValidationException) {
            $this->assertSame('ABIERTA', $i->fresh()->estado->value);
        }
    }

    public function test_state_permission_required_for_incidence_opening_and_recovery_but_not_simple_close(): void
    {
        $a = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $service = app(IncidentMaintenanceService::class);
        $role = Rol::where('nombre', 'Administrador')->sole();
        $permission = Permiso::where('codigo', 'equipamientos.cambiar_estado')->sole();
        $role->permisos()->detach($permission);
        try {
            $service->open($a, $this->incidentData($e->getKey()));
            $this->fail();
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('incidencias_mantenimiento', 0);
        }
        $e->forceFill(['estado' => 'EN_MANTENIMIENTO'])->save();
        $i = $service->open($a, $this->incidentData($e->getKey()));
        try {
            $service->close($a, $i, ['solucion' => 'Cierre', 'apto' => true]);
            $this->fail();
        } catch (AuthorizationException) {
            $this->assertSame('ABIERTA', $i->fresh()->estado->value);
        }
        $service->close($a, $i, ['solucion' => 'Cierre conservando restricción']);
        $this->assertSame('CERRADA', $i->fresh()->estado->value);
        $this->assertSame('EN_MANTENIMIENTO', $e->fresh()->estado->value);
    }

    public function test_http_common_workflow_and_affected_commitments_without_sensitive_fields(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->login($a);
        $this->get('/incidencias/crear?recurso_id='.$p->equipamiento_id)->assertOk();
        $this->post('/incidencias', $this->incidentData($p->equipamiento_id))->assertRedirect();
        $i = IncidenciaMantenimiento::sole();
        $this->get('/incidencias')->assertOk();
        $this->get('/incidencias/'.$i->id)->assertOk()->assertInertia(fn ($v) => $v->where('compromisos', 1)->where('prestamos.data.0.id', $p->id)->missing('incidencia.reportado_por.correo')->missing('incidencia.reportado_por.password_hash'));
        $this->post('/incidencias/'.$i->id.'/cerrar', ['solucion' => 'Reparación validada', 'apto' => true])->assertRedirect();
        $this->assertSame('PRESTADO', $p->equipamiento->fresh()->estado->value);
        $this->delete('/incidencias/'.$i->id)->assertStatus(405);
    }
}
