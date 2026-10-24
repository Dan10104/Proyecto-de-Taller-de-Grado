<?php

namespace Tests\Feature;

use App\Enums\TipoOperacionQr as Type;
use App\Models\EquipamientoTecnologico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\ValidacionQr;
use App\Services\EquipmentService;
use App\Services\IncidentMaintenanceService;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\QrTestCase;

class QrOperationTest extends QrTestCase
{
    public function test_delivery_and_return_reuse_resource_code_and_link_same_evidence_atomically(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $t = $this->token();
        $c = $this->code($t, ['recurso_id' => $p->equipamiento_id]);
        $v = $this->qr()->operation($a, $p, Type::ENTREGA, $t, $this->deliveryData());
        $this->assertSame('VALIDA', $v->resultado->value);
        $this->assertSame($p->fresh()->entrega->id, $v->entrega_id);
        $this->assertNull($v->devolucion_id);
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->fresh()->estado->value);
        $r = $this->qr()->operation($a, $p, Type::DEVOLUCION, $t, ['condicion' => 'ADECUADA']);
        $this->assertSame('VALIDA', $r->resultado->value);
        $this->assertSame($p->fresh()->devolucion->id, $r->devolucion_id);
        $this->assertNull($r->entrega_id);
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
        $this->assertSame('DISPONIBLE', $p->equipamiento->fresh()->estado->value);
        $this->assertDatabaseCount('validaciones_qr', 2);
        $this->assertTrue($c->fresh()->activo);
        foreach ([$v, $r] as $e) {
            $this->assertSame($a->id, $e->validado_por_id);
            $this->assertSame($c->id, $e->codigo_qr_id);
            $this->assertSame(hash('sha256', $t), $e->hash_leido);
            $this->assertNull($e->motivo_rechazo);
        }
    }

    public function test_exact_loan_goal_is_accepted_for_both_operations(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $t = $this->token();
        $this->code($t, ['prestamo_id' => $p->id]);
        foreach ([Type::ENTREGA, Type::DEVOLUCION] as $type) {
            $v = $this->qr()->operation($a, $p, $type, $t, $type === Type::ENTREGA ? $this->deliveryData() : ['condicion' => 'ADECUADA']);
            $this->assertSame('VALIDA', $v->resultado->value);
            $this->assertSame($p->id, $v->prestamo_id);
        }
    }

    public function test_unknown_inactive_expired_wrong_resource_and_wrong_operation_never_deliver(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $before = $p->fresh()->getRawOriginal();
        $equipment = $p->equipamiento->fresh()->getRawOriginal();
        $cases = [null, ['recurso_id' => $p->equipamiento_id, 'activo' => false], ['prestamo_id' => $p->id, 'generada_en' => now()->subHour(), 'vence_en' => now()->subSecond()], ['recurso_id' => EquipamientoTecnologico::factory()->create()->getKey()], ['prestamo_id' => $this->loan(null, $p->equipamiento)->id], ['reserva_id' => $this->reservation()->id]];
        foreach ($cases as $attributes) {
            $t = $this->token();
            $c = $attributes ? $this->code($t, $attributes) : null;
            $v = $this->qr()->operation($a, $p, Type::ENTREGA, $t, $this->deliveryData());
            $this->assertSame('INVALIDA', $v->resultado->value);
            $this->assertSame($c?->id, $v->codigo_qr_id);
            $this->assertNotEmpty($v->motivo_rechazo);
            $this->assertNull($v->entrega_id);
            $this->assertSame($before, $p->fresh()->getRawOriginal());
            $this->assertSame($equipment, $p->equipamiento->fresh()->getRawOriginal());
        }
        $this->assertDatabaseCount('validaciones_qr', count($cases));
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->assertDatabaseCount('devoluciones_prestamo', 0);
    }

    public function test_current_business_state_duplicate_milestones_and_pending_review_reject_new_attempts(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $t = $this->token();
        $this->code($t, ['prestamo_id' => $p->id]);
        $this->assertSame('INVALIDA', $this->qr()->operation($a, $p, Type::ENTREGA, $t, $this->deliveryData())->resultado->value);
        $this->loans()->approve($a, $p);
        $this->qr()->operation($a, $p, Type::ENTREGA, $t, $this->deliveryData());
        $this->assertSame('INVALIDA', $this->qr()->operation($a, $p, Type::ENTREGA, $t, $this->deliveryData())->resultado->value);
        $v = $this->qr()->operation($a, $p, Type::DEVOLUCION, $t, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Inspección pendiente']);
        $this->assertSame('VALIDA', $v->resultado->value);
        $id = $v->devolucion_id;
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('INVALIDA', $this->qr()->operation($a, $p, Type::DEVOLUCION, $t, ['condicion' => 'ADECUADA'])->resultado->value);
        $this->loans()->confirmReview($a, $p, ['condicion' => 'ADECUADA', 'apto' => true]);
        $this->assertSame($id, $p->fresh()->devolucion->id);
        $this->assertSame($id, $v->fresh()->devolucion_id);
        $this->assertDatabaseCount('entregas_prestamo', 1);
        $this->assertDatabaseCount('devoluciones_prestamo', 1);
        $this->assertSame(1, ValidacionQr::whereNotNull('devolucion_id')->count());
    }

    public function test_physical_return_accepts_baja_maintenance_and_incidents_without_releasing_restrictions(): void
    {
        $a = $this->user('Administrador');
        foreach (['BAJA', 'EN_MANTENIMIENTO', 'INCIDENCIA'] as $state) {
            $p = $this->delivered($a);
            $t = $this->token();
            $this->code($t, ['recurso_id' => $p->equipamiento_id]);
            if ($state === 'INCIDENCIA') {
                app(IncidentMaintenanceService::class)->open($a, $this->incidentData($p->equipamiento_id));
            } else {
                app(EquipmentService::class)->state($a, $p->equipamiento, $state);
            }
            $v = $this->qr()->operation($a, $p, Type::DEVOLUCION, $t, ['condicion' => 'ADECUADA']);
            $this->assertSame('VALIDA', $v->resultado->value);
            $this->assertNotNull($v->devolucion_id);
            $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
            $this->assertSame($state === 'BAJA' ? 'BAJA' : 'EN_MANTENIMIENTO', $p->equipamiento->fresh()->estado->value);
        }
    }

    public function test_return_with_incident_correlates_all_three_records(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $t = $this->token();
        $this->code($t, ['prestamo_id' => $p->id]);
        $v = $this->qr()->operation($a, $p, Type::DEVOLUCION, $t, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Daño sintético']);
        $i = IncidenciaMantenimiento::sole();
        $this->assertSame($v->devolucion_id, $i->devolucion_id);
        $this->assertSame($p->equipamiento_id, $i->recurso_id);
        $this->assertSame('EN_MANTENIMIENTO', $p->equipamiento->fresh()->estado->value);
    }

    public function test_failure_linking_validation_rolls_back_delivery_loan_equipment_and_evidence(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $t = $this->token();
        $this->code($t, ['prestamo_id' => $p->id]);
        ValidacionQr::updating(fn () => throw new \RuntimeException('Fallo de correlación sintético'));
        try {
            $this->qr()->operation($a, $p, Type::ENTREGA, $t, $this->deliveryData());
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo de correlación sintético', $e->getMessage());
        } finally {
            ValidacionQr::flushEventListeners();
        }
        $this->assertDatabaseCount('validaciones_qr', 0);
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->assertSame('APROBADO', $p->fresh()->estado->value);
        $this->assertSame('DISPONIBLE', $p->equipamiento->fresh()->estado->value);
    }

    public function test_failure_after_return_before_incident_rolls_back_evidence_and_all_business_changes(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $t = $this->token();
        $this->code($t, ['prestamo_id' => $p->id]);
        IncidenciaMantenimiento::creating(fn () => throw new \RuntimeException('Fallo sintético'));
        try {
            $this->qr()->operation($a, $p, Type::DEVOLUCION, $t, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Daño']);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo sintético', $e->getMessage());
        } finally {
            IncidenciaMantenimiento::flushEventListeners();
        }
        $this->assertDatabaseCount('validaciones_qr', 0);
        $this->assertDatabaseCount('devoluciones_prestamo', 0);
        $this->assertDatabaseCount('incidencias_mantenimiento', 0);
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->fresh()->estado->value);
    }

    public function test_revoked_qr_core_and_context_permissions_deny_before_evidence(): void
    {
        foreach (['qr.validar_operacion', 'prestamos.registrar_entrega', 'prestamos.ver_administrativos'] as $permission) {
            $a = $this->user('Encargado de Laboratorio');
            $p = $this->loan();
            $this->loans()->approve($a, $p);
            $t = $this->token();
            $this->code($t, ['prestamo_id' => $p->id]);
            $role = Rol::where('nombre', 'Encargado de Laboratorio')->sole();
            $id = Permiso::where('codigo', $permission)->sole()->id;
            $role->permisos()->detach($id);
            try {
                $this->qr()->operation($a, $p, Type::ENTREGA, $t, $this->deliveryData());
                $this->fail();
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('validaciones_qr', 0);
                $this->assertDatabaseCount('entregas_prestamo', 0);
            }
            $role->permisos()->attach($id);
        }
    }
}
