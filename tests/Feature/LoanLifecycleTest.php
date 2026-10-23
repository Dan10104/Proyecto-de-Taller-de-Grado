<?php

namespace Tests\Feature;

use App\Models\EquipamientoTecnologico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Permiso;
use App\Models\Rol;
use App\Services\EquipmentService;
use App\Services\IncidentMaintenanceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\LoanTestCase;

class LoanLifecycleTest extends LoanTestCase
{
    public function test_pending_coexist_and_code_is_unique_immutable_v4(): void
    {
        $owner = $this->user('Docente');
        $e = EquipamientoTecnologico::factory()->create();
        $data = $this->loanData($e);
        $a = $this->loans()->create($owner, $data);
        $b = $this->loans()->create($owner, $data);
        $this->assertNotSame($a->codigo, $b->codigo);
        $this->assertSame('PENDIENTE', $a->estado->value);
        $this->assertNull($a->resuelto_en);
        $this->assertNull($a->resuelto_por_id);
        $this->assertSame($owner->id, $a->solicitante_id);
        $this->assertSame(40, strlen($a->codigo));
        $this->assertMatchesRegularExpression('/^PRE-[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $a->codigo);
        $a->codigo = 'PRE-EDIT';
        $this->expectException(\LogicException::class);
        $a->save();
    }

    public function test_collision_retries_only_code_and_is_bounded(): void
    {
        $p = $this->loan();
        $uuid = substr($p->codigo, 4);
        $owner = $p->solicitante;
        $this->grantRole($owner, 'Docente');
        $count = 0;
        Str::createUuidsUsing(function () use (&$count, $uuid) {
            $count++;

            return $uuid;
        });
        try {
            $this->conflict(fn () => $this->loans()->create($owner, $this->loanData($p->equipamiento)));
            $this->assertSame(3, $count);
            $this->assertDatabaseCount('prestamos', 1);
        } finally {
            Str::createUuidsNormally();
        }
        $new = (string) Str::uuid();
        Str::createUuidsUsingSequence([$uuid, $new]);
        try {
            $q = $this->loans()->create($owner, $this->loanData($p->equipamiento));
            $this->assertSame('PRE-'.$new, $q->codigo);
        } finally {
            Str::createUuidsNormally();
        }
    }

    public function test_resolution_overlap_loser_and_rejection_required_even_when_unavailable(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $other = $this->loan(null, $p->equipamiento);
        $this->loans()->approve($a, $p);
        $this->conflict(fn () => $this->loans()->approve($a, $other));
        $this->assertSame('PENDIENTE', $other->fresh()->estado->value);
        $this->assertNull($other->fresh()->resuelto_en);
        $this->assertNull($other->fresh()->resuelto_por_id);
        foreach (['', '  ', str_repeat('x', 501)] as $bad) {
            try {
                $this->loans()->reject($a, $other, $bad);
                $this->fail();
            } catch (ValidationException) {
                $this->assertSame('PENDIENTE', $other->fresh()->estado->value);
            }
        }
        app(EquipmentService::class)->state($a, $other->equipamiento, 'BAJA');
        $this->loans()->reject($a, $other, 'No disponible');
        $this->assertSame('RECHAZADO', $other->fresh()->estado->value);
        $this->assertSame($a->id, $other->fresh()->resuelto_por_id);
        $this->conflict(fn () => $this->loans()->approve($a, $other));
        $this->conflict(fn () => $this->loans()->reject($a, $p, 'No sobrescribir'));
    }

    public function test_delivery_uses_server_actors_and_condition_and_unique_hito(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $p = $this->loans()->deliver($a, $p, $this->deliveryData());
        $d = $p->entrega;
        $this->assertSame($a->id, $d->entregado_por_id);
        $this->assertSame($p->solicitante_id, $d->receptor_id);
        $this->assertNull($d->recepcion_confirmada_en);
        $this->assertSame('ENTREGADO', $p->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->estado->value);
        $before = $d->getRawOriginal();
        $this->conflict(fn () => $this->loans()->deliver($a, $p, $this->deliveryData(['condicion_entrega' => 'Otro intento'])));
        $this->assertSame($before, $d->fresh()->getRawOriginal());
        $this->assertDatabaseCount('entregas_prestamo', 1);
    }

    public function test_adequate_return_confirms_and_recovers_without_touching_delivery(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $d = $p->entrega->getRawOriginal();
        $this->loans()->receive($a, $p, ['condicion' => 'ADECUADA']);
        $p->refresh();
        $r = $p->devolucion;
        $this->assertSame('DEVUELTO', $p->estado->value);
        $this->assertSame('DISPONIBLE', $p->equipamiento->estado->value);
        $this->assertSame('CONFIRMADA', $r->estado->value);
        $this->assertSame($a->id, $r->recibido_por_id);
        $this->assertSame($a->id, $r->confirmado_por_id);
        $this->assertTrue($r->confirmada_en >= $r->registrada_en);
        $this->assertSame($d, $p->entrega->getRawOriginal());
        $this->conflict(fn () => $this->loans()->receive($a, $p, ['condicion' => 'ADECUADA']));
    }

    public function test_return_with_incident_is_atomic_and_linked_to_same_resource(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->loans()->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Conector dañado']);
        $p->refresh();
        $i = IncidenciaMantenimiento::sole();
        $this->assertSame($p->equipamiento_id, $i->recurso_id);
        $this->assertSame($p->devolucion->id, $i->devolucion_id);
        $this->assertSame($a->id, $i->reportado_por_id);
        $this->assertSame('ABIERTA', $i->estado->value);
        $this->assertSame('DEVUELTO', $p->estado->value);
        $this->assertSame('EN_MANTENIMIENTO', $p->equipamiento->estado->value);
    }

    public function test_unknown_return_and_later_review_preserve_reception_and_require_explicit_recovery(): void
    {
        $a = $this->user('Administrador');
        $b = $this->user('Encargado de Laboratorio');
        $p = $this->delivered($a);
        $this->loans()->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Requiere inspección']);
        $p->refresh();
        $r = $p->devolucion;
        $received = $r->registrada_en;
        $id = $r->id;
        $this->assertSame('ENTREGADO', $p->estado->value);
        $this->assertSame('EN_MANTENIMIENTO', $p->equipamiento->estado->value);
        $this->assertSame('PENDIENTE_REVISION', $r->estado->value);
        $this->assertNull($r->confirmada_en);
        $this->assertNull($r->confirmado_por_id);
        $this->conflict(fn () => app(EquipmentService::class)->state($a, $p->equipamiento, 'DISPONIBLE', true));
        $this->loans()->confirmReview($b, $p, ['condicion' => 'ADECUADA', 'apto' => true]);
        $r->refresh();
        $this->assertSame($id, $r->id);
        $this->assertSame($a->id, $r->recibido_por_id);
        $this->assertTrue($received->equalTo($r->registrada_en));
        $this->assertSame($b->id, $r->confirmado_por_id);
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
        $this->assertSame('DISPONIBLE', $p->equipamiento->fresh()->estado->value);
        $this->assertDatabaseCount('devoluciones_prestamo', 1);
        $this->conflict(fn () => $this->loans()->confirmReview($b, $p, ['condicion' => 'ADECUADA']));
    }

    public function test_review_can_end_with_incident_and_does_not_close_existing_one(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $old = app(IncidentMaintenanceService::class)->open($a, $this->incidentData($p->equipamiento_id));
        $this->loans()->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisión']);
        $this->loans()->confirmReview($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Daño confirmado']);
        $this->assertSame('ABIERTA', $old->fresh()->estado->value);
        $this->assertDatabaseCount('incidencias_mantenimiento', 2);
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
    }

    public function test_baja_and_administrative_maintenance_survive_returns(): void
    {
        $a = $this->user('Administrador');
        foreach (['BAJA', 'EN_MANTENIMIENTO'] as $state) {
            $p = $this->delivered($a);
            app(EquipmentService::class)->state($a, $p->equipamiento, $state);
            $this->loans()->receive($a, $p, ['condicion' => 'ADECUADA']);
            $this->assertSame($state, $p->equipamiento->fresh()->estado->value);
            $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
        }
    }

    public function test_with_incident_requires_both_permissions_and_rolls_back(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'incidencias.gestionar')->sole());
        try {
            $this->loans()->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Daño']);
            $this->fail();
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('devoluciones_prestamo', 0);
            $this->assertDatabaseCount('incidencias_mantenimiento', 0);
            $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        }
    }

    public function test_failure_after_delivery_insert_rolls_back_everything(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        EquipamientoTecnologico::updating(function () {
            throw new \RuntimeException('Fallo sintético');
        });
        try {
            $this->loans()->deliver($a, $p, $this->deliveryData());
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo sintético', $e->getMessage());
        } finally {
            EquipamientoTecnologico::flushEventListeners();
        }
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->assertSame('APROBADO', $p->fresh()->estado->value);
        $this->assertSame('DISPONIBLE', $p->equipamiento->estado->value);
    }

    public function test_failure_after_return_before_incident_rolls_back_everything(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        IncidenciaMantenimiento::creating(function () {
            throw new \RuntimeException('Fallo de incidencia sintético');
        });
        try {
            $this->loans()->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Daño']);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo de incidencia sintético', $e->getMessage());
        } finally {
            IncidenciaMantenimiento::flushEventListeners();
        }
        $this->assertDatabaseCount('devoluciones_prestamo', 0);
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->estado->value);
    }

    public function test_vencido_is_derived_strict_and_review_stays_visible(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->travelTo($p->fin_previsto);
        $this->assertFalse($p->vencido());
        $this->travel(1)->seconds();
        $this->assertTrue($p->vencido());
        $this->loans()->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisión']);
        $this->assertTrue($p->fresh()->vencido());
        $this->loans()->confirmReview($a, $p, ['condicion' => 'ADECUADA']);
        $this->assertFalse($p->fresh()->vencido());
        $this->travelBack();
    }

    public function test_failed_final_equipment_write_rolls_back_return_and_loan_state(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        EquipamientoTecnologico::updating(function () {
            throw new \RuntimeException('Fallo final sintético');
        });
        try {
            $this->loans()->receive($a, $p, ['condicion' => 'ADECUADA']);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo final sintético', $e->getMessage());
        } finally {
            EquipamientoTecnologico::flushEventListeners();
        }
        $this->assertDatabaseCount('devoluciones_prestamo', 0);
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->estado->value);
    }

    public function test_review_without_state_permission_confirms_but_cannot_release_administrative_maintenance(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->loans()->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisar']);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'equipamientos.cambiar_estado')->sole());
        try {
            $this->loans()->confirmReview($a, $p, ['condicion' => 'ADECUADA', 'apto' => true]);
            $this->fail();
        } catch (AuthorizationException) {
            $this->assertSame('PENDIENTE_REVISION', $p->devolucion->estado->value);
        }
        $this->loans()->confirmReview($a, $p, ['condicion' => 'ADECUADA']);
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
        $this->assertSame('EN_MANTENIMIENTO', $p->equipamiento->fresh()->estado->value);
    }

    public function test_lock_order_covers_resource_target_conflicts_delivery_return_then_incidence(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            if (str_contains($q->sql, 'for update')) {
                $queries[] = $q->sql;
            }
        });
        $this->loans()->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Inspección']);
        $tables = ['roles', 'equipamientos_tecnologicos', 'prestamos', 'entregas_prestamo', 'devoluciones_prestamo', 'incidencias_mantenimiento'];
        $last = -1;
        foreach ($tables as $table) {
            $position = null;
            foreach ($queries as $i => $query) {
                if (str_starts_with($query, 'select * from '.chr(96).$table.chr(96))) {
                    $position = $i;
                    break;
                }
            }$this->assertNotNull($position, $table);
            $this->assertGreaterThan($last, $position);
            $last = $position;
        }
    }
}
