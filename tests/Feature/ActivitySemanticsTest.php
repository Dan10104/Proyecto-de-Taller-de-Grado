<?php

namespace Tests\Feature;

use App\Enums\ActivityAction as Action;
use App\Models\CodigoQr;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\Permiso;
use App\Models\PoliticaLiberacion;
use App\Models\Prestamo;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\SensorIot;
use App\Services\Activity\ActivityDescription;
use App\Services\Activity\ActivityQueryService;
use App\Services\EquipmentService;
use App\Services\IotManagementService;
use App\Services\QrManagementService;
use App\Services\ReleasePolicyManagementService;
use App\Services\Reporting\ActivityReportQuery;
use App\Services\Reporting\ReportAuditService;
use App\Services\SpaceManagementService;
use Illuminate\Support\Facades\DB;
use Tests\ActivityTestCase;

class ActivitySemanticsTest extends ActivityTestCase
{
    public function test_state_transition_in_edit_has_precedence_and_repeat_is_noop(): void
    {
        $admin = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        $data = $this->spaceData(['estado' => 'BLOQUEADO']);
        app(SpaceManagementService::class)->save($admin, $space, $data);
        app(SpaceManagementService::class)->save($admin, $space, $data);
        $this->assertSame('ESPACIO_ESTADO_CAMBIADO', RegistroActividad::sole()->accion);
        $equipment = EquipamientoTecnologico::factory()->create();
        $data = $this->equipmentData(['estado' => 'BAJA']);
        app(EquipmentService::class)->save($admin, $equipment, $data);
        app(EquipmentService::class)->save($admin, $equipment, $data);
        $this->assertSame(['ESPACIO_ESTADO_CAMBIADO', 'EQUIPAMIENTO_ESTADO_CAMBIADO'], RegistroActividad::orderBy('id')->pluck('accion')->all());
    }

    public function test_inactive_qr_history_is_rotation_and_internal_deactivation_is_not_a_second_event(): void
    {
        $admin = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        CodigoQr::factory()->create(['recurso_id' => $space->getKey(), 'activo' => false]);
        $qr = app(QrManagementService::class);
        $first = $qr->issue($admin, 'recurso', $space->getKey());
        $second = $qr->issue($admin, 'recurso', $space->getKey());
        $this->assertSame(['QR_ROTADO', 'QR_ROTADO'], RegistroActividad::orderBy('id')->pluck('accion')->all());
        $qr->deactivate($admin, 'recurso', $space->getKey(), $second['id']);
        $qr->deactivate($admin, 'recurso', $space->getKey(), $second['id']);
        $this->assertDatabaseCount('registros_actividad', 3);
        $this->assertSame(0, CodigoQr::where('activo', true)->count());
        foreach (CodigoQr::pluck('token_hash') as $hash) {
            $this->assertStringNotContainsString($hash, RegistroActividad::get()->toJson());
        }
    }

    public function test_qr_reservation_and_loan_contexts_are_historical_foreign_keys(): void
    {
        $admin = $this->user('Administrador');
        $r = Reserva::factory()->create();
        $p = Prestamo::factory()->create();
        app(QrManagementService::class)->issue($admin, 'reserva', $r->id);
        app(QrManagementService::class)->issue($admin, 'prestamo', $p->id);
        $rows = RegistroActividad::orderBy('id')->get();
        $this->assertSame([$r->solicitante_id, $r->espacio_id, $r->id, null], [$rows[0]->usuario_afectado_id, $rows[0]->recurso_id, $rows[0]->reserva_id, $rows[0]->prestamo_id]);
        $this->assertSame([$p->solicitante_id, $p->equipamiento_id, null, $p->id], [$rows[1]->usuario_afectado_id, $rows[1]->recurso_id, $rows[1]->reserva_id, $rows[1]->prestamo_id]);
    }

    public function test_policy_replacement_is_one_event_and_sensor_reassignment_preserves_destination_at_event(): void
    {
        $admin = $this->user('Administrador');
        $old = PoliticaLiberacion::factory()->create();
        $new = PoliticaLiberacion::factory()->create();
        $space = EspacioAcademico::factory()->create(['politica_liberacion_id' => $old->id]);
        app(ReleasePolicyManagementService::class)->assign($admin, $space->getKey(), $new->id);
        $this->assertSame('POLITICA_ASIGNADA_ESPACIO', RegistroActividad::sole()->accion);
        $sensor = SensorIot::factory()->create();
        app(IotManagementService::class)->save($admin, $sensor, ['tipo' => 'PRIVATE', 'espacio_actual_id' => $space->getKey()]);
        $row = RegistroActividad::orderByDesc('id')->first();
        $this->assertSame('SENSOR_REASIGNADO', $row->accion);
        $this->assertSame($space->getKey(), $row->recurso_id);
        $sensor->forceFill(['espacio_actual_id' => EspacioAcademico::factory()->create()->getKey()])->save();
        $this->assertSame($space->getKey(), $row->fresh()->recurso_id);
    }

    public function test_evidence_link_requires_current_permission_and_never_triggers_verification(): void
    {
        $admin = $this->user('Administrador');
        $id = DB::table('registros_actividad')->insertGetId(['actor_id' => $admin->id, 'accion' => 'RESERVA_APROBADA', 'critica' => true, 'resultado' => 'EXITO', 'fecha_hora' => now()]);
        $e = DB::table('evidencias_trazabilidad')->insertGetId(['registro_actividad_id' => $id, 'algoritmo_hash' => 'SHA-256', 'hash' => hash('sha256', 'synthetic-audit-fixture'), 'version_canonica' => 'fixture']);
        $before = DB::table('evidencias_trazabilidad')->find($e);
        $dto = app(ActivityQueryService::class)->detail($admin, $id);
        $this->assertSame('/trazabilidad/'.$e, $dto['evidencia']['url']);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'trazabilidad.evidencias.ver')->value('id'));
        $this->assertNull(app(ActivityQueryService::class)->detail($admin, $id)['evidencia']['url']);
        $this->assertEquals($before, DB::table('evidencias_trazabilidad')->find($e));
        $this->assertDatabaseCount('registros_actividad', 1);
    }

    public function test_legacy_export_description_accepts_only_closed_typed_metadata_and_no_new_reporting_actions(): void
    {
        $admin = $this->user('Administrador');
        app(ReportAuditService::class)->record($admin, ['reporte' => 'reservas', 'generado_en' => now()->toDateTimeString(), 'filtros' => ['periodo' => 'hoy', 'estado' => 'APROBADA']], 'pdf', 2);
        $description = RegistroActividad::sole()->descripcion;
        $this->assertSame($description, ActivityDescription::safe(Action::REPORTE_EXPORTADO, $description));
        $this->assertNull(ActivityDescription::safe(Action::REPORTE_EXPORTADO, str_replace('APROBADA', 'SECRET-FREE-TEXT', $description)));
        $this->assertNull(ActivityDescription::safe(Action::REPORTE_EXPORTADO, $description.'PRIVATE'));
        $this->assertCount(18, array_merge(...array_column(ActivityReportQuery::FAMILIES, 'actions')));
    }
}
