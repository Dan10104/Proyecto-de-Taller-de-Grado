<?php

namespace Tests\Feature;

use App\Enums\ActivityAction as Action;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\Permiso;
use App\Models\PoliticaLiberacion;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\SensorIot;
use App\Models\Usuario;
use App\Services\Activity\ActivityAttempt;
use App\Services\Activity\ActivityRecorder;
use App\Services\Activity\ActivityTransaction;
use App\Services\AdministrationService;
use App\Services\EquipmentService;
use App\Services\IotManagementService;
use App\Services\ReleasePolicyManagementService;
use App\Services\SpaceManagementService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ActivityTestCase;

class ActivityIntegrityTest extends ActivityTestCase
{
    public static function rejectionActions(): array
    {
        return array_map(fn ($a) => [$a], array_values(array_filter(Action::cases(), fn ($a) => $a->rejectable())));
    }

    #[DataProvider('rejectionActions')]
    public function test_only_nine_contract_guards_record_rejection_after_business_rollback(Action $action): void
    {
        $admin = $this->user('Administrador');
        $role = Rol::where('nombre', 'Administrador')->sole();
        $space = EspacioAcademico::factory()->create(['estado' => 'BLOQUEADO']);
        $this->blockFor($space, now()->subHour()->toDateTimeString(), now()->addHour()->toDateTimeString(), true, $admin);
        $equipment = EquipamientoTecnologico::factory()->create();
        $sensor = SensorIot::factory()->create(['estado' => 'DESHABILITADO']);
        $policy = PoliticaLiberacion::factory()->create(['activa' => false]);
        $space->forceFill(['politica_liberacion_id' => $policy->id])->save();
        Reserva::factory()->aprobada()->create(['espacio_id' => $space->getKey(), 'inicio' => now()->addHour(), 'fin' => now()->addHours(2)]);
        $operation = match ($action) {
            Action::USUARIO_DESHABILITADO => fn () => app(AdministrationService::class)->disableUser($admin, $admin),
            Action::ROL_RETIRADO => fn () => app(AdministrationService::class)->withdraw($admin, $admin, $role),
            Action::PERMISOS_ROL_ACTUALIZADOS => fn () => app(AdministrationService::class)->setPermissions($admin, $role, []),
            Action::ESPACIO_ESTADO_CAMBIADO => fn () => app(SpaceManagementService::class)->state($admin, $space, 'DISPONIBLE'),
            Action::EQUIPAMIENTO_ESTADO_CAMBIADO => fn () => app(EquipmentService::class)->state($admin, $equipment, 'PRESTADO'),
            Action::SENSOR_CREDENCIAL_CONSULTADA => fn () => app(IotManagementService::class)->provision($admin, $sensor),
            Action::POLITICA_ASIGNADA_ESPACIO => fn () => app(ReleasePolicyManagementService::class)->assign($admin, $space->getKey(), PoliticaLiberacion::factory()->create()->id),
            Action::POLITICA_RETIRADA_ESPACIO => fn () => app(ReleasePolicyManagementService::class)->assign($admin, $space->getKey(), null),
            Action::POLITICA_LIBERACION_ACTIVADA => fn () => app(ReleasePolicyManagementService::class)->state($admin, $policy->id, true),
        };
        // The alternative version is fixture setup, outside the audited operation.
        if ($action === Action::POLITICA_ASIGNADA_ESPACIO) {
            $new = PoliticaLiberacion::factory()->create();
            $operation = fn () => app(ReleasePolicyManagementService::class)->assign($admin, $space->getKey(), $new->id);
        }
        $before = $this->businessSnapshot();
        $caught = false;
        try {
            $operation();
        } catch (\Throwable) {
            $caught = true;
        }
        $this->assertTrue($caught);
        $this->assertSame($before, $this->businessSnapshot());
        $row = RegistroActividad::sole();
        $this->assertSame($action->value, $row->accion);
        $this->assertSame('RECHAZO', $row->resultado->value);
        $this->assertSame($admin->id, $row->actor_id);
        $this->assertFalse($row->critica);
        $this->assertSame('Operación rechazada por una guarda funcional.', $row->descripcion);
    }

    public function test_missing_policy_keeps_validation_response_without_auditing_an_unresolved_target(): void
    {
        $admin = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        try {
            app(ReleasePolicyManagementService::class)->assign($admin, $space->getKey(), 999999);
            $this->fail('La política inexistente debe rechazarse como validación.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('politica_id', $e->errors());
        }
        $this->assertNull($space->fresh()->politica_liberacion_id);
        $this->assertSame(0, RegistroActividad::count());
    }

    public function test_role_eligibility_guard_is_rejection_but_unknown_permission_is_only_validation(): void
    {
        $admin = $this->user('Administrador');
        $role = Rol::where('nombre', 'Estudiante')->sole();
        foreach ([999999, Permiso::where('codigo', 'usuarios.editar')->value('id')] as $id) {
            try {
                app(AdministrationService::class)->setPermissions($admin, $role, [$id]);
            } catch (ValidationException) {
            }
        }
        $this->assertSame('RECHAZO', RegistroActividad::sole()->resultado->value);
    }

    public function test_noop_updates_states_assignments_and_policy_removal_do_not_record_success(): void
    {
        $admin = $this->user('Administrador');
        $target = $this->user();
        $role = Rol::where('nombre', 'Estudiante')->sole();
        $administration = app(AdministrationService::class);
        $administration->updateUser($admin, $target, ['nombres' => $target->nombres]);
        $administration->saveRole($admin, $role, ['nombre' => $role->nombre, 'descripcion' => $role->descripcion]);
        $administration->setPermissions($admin, $role, $role->permisos()->pluck('permisos.id')->all());
        $administration->withdraw($admin, $target, $role);
        $space = EspacioAcademico::factory()->create();
        app(SpaceManagementService::class)->state($admin, $space, 'DISPONIBLE');
        $equipment = EquipamientoTecnologico::factory()->create();
        app(EquipmentService::class)->state($admin, $equipment, 'DISPONIBLE', true);
        $sensor = SensorIot::factory()->create();
        app(IotManagementService::class)->state($admin, $sensor, $sensor->estado->value);
        app(ReleasePolicyManagementService::class)->assign($admin, $space->getKey(), null);
        $this->assertDatabaseCount('registros_actividad', 0);
    }

    public function test_user_with_roles_is_atomic_and_child_failure_leaves_only_root_error(): void
    {
        $admin = $this->user('Administrador');
        $role = Rol::where('nombre', 'Estudiante')->sole();
        $data = $this->userData(['roles' => [$role->id]]);
        $user = app(AdministrationService::class)->createUser($admin, $data);
        $this->assertSame(['USUARIO_CREADO', 'ROL_ASIGNADO'], RegistroActividad::orderBy('id')->pluck('accion')->all());
        $this->assertSame([$user->id], RegistroActividad::pluck('usuario_afectado_id')->unique()->all());
        $before = $this->businessSnapshot();
        $this->partialMock(ActivityRecorder::class, function ($mock) {
            $mock->shouldReceive('success')->withArgs(fn ($action) => $action === Action::ROL_ASIGNADO)->once()->andThrow(new \RuntimeException('PRIVATE-CHILD'));
        });
        try {
            app(AdministrationService::class)->createUser($admin, $this->userData(['roles' => [$role->id]]));
        } catch (\RuntimeException) {
        }
        $this->assertSame($before, $this->businessSnapshot());
        $this->assertDatabaseCount('registros_actividad', 3);
        $last = RegistroActividad::orderByDesc('id')->first();
        $this->assertSame('USUARIO_CREADO', $last->accion);
        $this->assertSame('ERROR', $last->resultado->value);
        $this->assertNull($last->usuario_afectado_id);
    }

    public static function invalidRecords(): array
    {
        return [[['accion' => 'UNKNOWN']], [['actor_id' => null]], [['descripcion' => str_repeat('x', 501)]], [['critica' => true]], [['accion' => 'RESERVA_APROBADA', 'critica' => false]], [['proceso_responsable' => 'invented']], [['descripcion' => 'PRIVATE-PAYLOAD']], [['resultado' => 'INVALID']], [['resultado' => 'RECHAZO']], [['actor_id' => -1]], [['extra' => 'data']]];
    }

    #[DataProvider('invalidRecords')]
    public function test_recorder_rejects_invalid_catalog_responsibility_description_and_criticality(array $overrides): void
    {
        $admin = $this->user('Administrador');
        $this->expectException(\InvalidArgumentException::class);
        app(ActivityRecorder::class)->record([...['actor_id' => $admin->id, 'accion' => 'USUARIO_CREADO'], ...$overrides]);
    }

    public function test_business_failure_after_success_rolls_back_success_and_preserves_only_error(): void
    {
        $admin = $this->user('Administrador');
        $audit = new ActivityAttempt($admin);
        try {
            app(ActivityTransaction::class)->run(function () use ($admin, $audit) {
                $audit->admit(Action::USUARIO_EDITADO, ['usuario_afectado_id' => $admin->id]);
                app(ActivityRecorder::class)->success(Action::USUARIO_EDITADO, $admin, $audit->context, ['campos' => ['nombres']]);
                throw new \RuntimeException('PRIVATE-BUSINESS');
            }, $audit);
        } catch (\RuntimeException) {
        }
        $this->assertSame('ERROR', RegistroActividad::sole()->resultado->value);
    }

    public function test_uncertain_connection_failure_is_not_falsely_classified_as_confirmed_rollback(): void
    {
        $admin = $this->user('Administrador');
        $audit = new ActivityAttempt($admin);
        try {
            app(ActivityTransaction::class)->run(function () use ($audit) {
                $audit->admit(Action::USUARIO_CREADO);
                throw new \RuntimeException('server has gone away');
            }, $audit);
        } catch (\RuntimeException) {
        }
        $this->assertDatabaseCount('registros_actividad', 0);
    }

    public function test_recorder_failure_does_not_recurse_or_confirm_business(): void
    {
        $admin = $this->user('Administrador');
        $before = Usuario::count();
        $this->partialMock(ActivityRecorder::class, fn ($mock) => $mock->shouldReceive('record')->twice()->andThrow(new \RuntimeException('PRIVATE-DB-DOWN')));
        try {
            app(AdministrationService::class)->createUser($admin, $this->userData());
        } catch (\RuntimeException) {
        }
        $this->assertSame($before, Usuario::count());
        $this->assertDatabaseCount('registros_actividad', 0);
    }
}
