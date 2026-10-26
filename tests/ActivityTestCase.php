<?php

namespace Tests;

use App\Enums\ActivityAction as Action;
use App\Models\CaracteristicaEspacio;
use App\Models\CodigoQr;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\PoliticaLiberacion;
use App\Models\Rol;
use App\Models\SensorIot;
use App\Models\TipoEquipamiento;
use App\Models\TipoEspacio;
use App\Models\Usuario;
use App\Services\AdministrationService;
use App\Services\EquipmentService;
use App\Services\IotManagementService;
use App\Services\QrManagementService;
use App\Services\ReleasePolicyManagementService;
use App\Services\SpaceManagementService;
use Illuminate\Support\Facades\DB;

abstract class ActivityTestCase extends LoanTestCase
{
    protected function operation(Action $action, Usuario $admin): callable
    {
        $administration = app(AdministrationService::class);
        $spaces = app(SpaceManagementService::class);
        $equipment = app(EquipmentService::class);
        $iot = app(IotManagementService::class);
        $qr = app(QrManagementService::class);
        $policies = app(ReleasePolicyManagementService::class);
        if (str_starts_with($action->value, 'USUARIO_')) {
            $user = $action === Action::USUARIO_CREADO ? null : $this->user();
            $data = $this->userData();

            return match ($action) {
                Action::USUARIO_CREADO => fn () => $administration->createUser($admin, $data),
                Action::USUARIO_EDITADO => fn () => $administration->updateUser($admin, $user, ['nombres' => 'CAMBIO-PRIVADO', 'password' => $this->password]),
                Action::USUARIO_DESHABILITADO => fn () => $administration->disableUser($admin, $user),
            };
        }
        if (str_starts_with($action->value, 'ROL_') || $action === Action::PERMISOS_ROL_ACTUALIZADOS) {
            $role = Rol::where('nombre', 'Estudiante')->sole();
            if ($action === Action::ROL_CREADO) {
                $role->permisos()->detach();
                $role->delete();

                return fn () => $administration->saveRole($admin, null, ['nombre' => 'Estudiante', 'descripcion' => 'PRIVADO']);
            }
            $user = $this->user($action === Action::ROL_RETIRADO ? 'Estudiante' : null);

            return match ($action) {
                Action::ROL_EDITADO => fn () => $administration->saveRole($admin, $role, ['nombre' => 'Estudiante', 'descripcion' => 'PRIVADO']),
                Action::PERMISOS_ROL_ACTUALIZADOS => fn () => $administration->setPermissions($admin, $role, []),
                Action::ROL_ASIGNADO => fn () => $administration->assign($admin, $user, $role),
                Action::ROL_RETIRADO => fn () => $administration->withdraw($admin, $user, $role),
            };
        }
        foreach ([['PISO_', 'pisos', Piso::class], ['TIPO_ESPACIO_', 'tipos', TipoEspacio::class], ['CARACTERISTICA_ESPACIO_', 'caracteristicas', CaracteristicaEspacio::class]] as [$prefix, $catalog, $class]) {
            if (str_starts_with($action->value, $prefix)) {
                $create = str_ends_with($action->value, 'CREADO') || str_ends_with($action->value, 'CREADA');
                $target = $create ? null : $class::factory()->create();
                $data = ['nombre' => 'Dato privado'];
                if ($catalog === 'pisos') {
                    $data['numero'] = 222;
                }
                if ($catalog === 'tipos') {
                    $data['codigo'] = 'TIPO-AUDIT';
                }

                return fn () => $spaces->saveCatalog($admin, $catalog, $target?->id, $data);
            }
        }
        if (str_starts_with($action->value, 'ESPACIO_') || str_starts_with($action->value, 'BLOQUEO_')) {
            $space = EspacioAcademico::factory()->create();
            $data = $this->spaceData();
            $block = $action === Action::BLOQUEO_ESPACIO_DESACTIVADO ? $this->blockFor($space, '2090-01-10 08:00', '2090-01-10 09:00', true, $admin) : null;

            return match ($action) {
                Action::ESPACIO_CREADO => fn () => $spaces->save($admin, null, $data),
                Action::ESPACIO_EDITADO => fn () => $spaces->save($admin, $space, $data),
                Action::ESPACIO_ESTADO_CAMBIADO => fn () => $spaces->state($admin, $space, 'BLOQUEADO'),
                Action::BLOQUEO_ESPACIO_CREADO => fn () => $spaces->block($admin, $space, ['inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00', 'motivo' => 'MOTIVO PRIVADO']),
                Action::BLOQUEO_ESPACIO_DESACTIVADO => fn () => $spaces->deactivate($admin, $space, $block->id),
            };
        }
        if (str_starts_with($action->value, 'TIPO_EQUIPAMIENTO_')) {
            $type = $action === Action::TIPO_EQUIPAMIENTO_CREADO ? null : TipoEquipamiento::factory()->create();

            return fn () => $equipment->saveType($admin, $type, ['codigo' => 'TIPO-AUDIT', 'nombre' => 'Dato privado']);
        }
        if (str_starts_with($action->value, 'EQUIPAMIENTO_')) {
            $target = EquipamientoTecnologico::factory()->create();
            $data = $this->equipmentData();

            return match ($action) {
                Action::EQUIPAMIENTO_CREADO => fn () => $equipment->save($admin, null, $data),
                Action::EQUIPAMIENTO_EDITADO => fn () => $equipment->save($admin, $target, $data),
                Action::EQUIPAMIENTO_ESTADO_CAMBIADO => fn () => $equipment->state($admin, $target, 'BAJA'),
            };
        }
        if (str_starts_with($action->value, 'SENSOR_')) {
            config(['iot.master_secret' => bin2hex(random_bytes(32))]);
            $sensor = SensorIot::factory()->create(['estado' => $action === Action::SENSOR_HABILITADO ? 'DESHABILITADO' : 'HABILITADO']);
            $space = EspacioAcademico::factory()->create();

            return match ($action) {
                Action::SENSOR_CREADO => fn () => $iot->save($admin, null, ['identificador' => 'AUDIT-DEVICE', 'tipo' => 'PRIVADO', 'espacio_actual_id' => $space->getKey()]),
                Action::SENSOR_EDITADO => fn () => $iot->save($admin, $sensor, ['tipo' => 'TIPO PRIVADO', 'espacio_actual_id' => $sensor->espacio_actual_id]),
                Action::SENSOR_REASIGNADO => fn () => $iot->save($admin, $sensor, ['tipo' => 'TIPO PRIVADO', 'espacio_actual_id' => $space->getKey()]),
                Action::SENSOR_HABILITADO => fn () => $iot->state($admin, $sensor, 'HABILITADO'),
                Action::SENSOR_DESHABILITADO => fn () => $iot->state($admin, $sensor, 'DESHABILITADO'),
                Action::SENSOR_CREDENCIAL_CONSULTADA => fn () => $iot->provision($admin, $sensor),
            };
        }
        if (str_starts_with($action->value, 'QR_')) {
            $space = EspacioAcademico::factory()->create();
            $code = $action !== Action::QR_GENERADO ? CodigoQr::factory()->create(['recurso_id' => $space->getKey()]) : null;

            return $action === Action::QR_DESACTIVADO ? fn () => $qr->deactivate($admin, 'recurso', $space->getKey(), $code->id) : fn () => $qr->issue($admin, 'recurso', $space->getKey());
        }
        $policy = PoliticaLiberacion::factory()->create(['activa' => $action !== Action::POLITICA_LIBERACION_ACTIVADA]);
        $space = EspacioAcademico::factory()->create(['politica_liberacion_id' => $action === Action::POLITICA_RETIRADA_ESPACIO ? $policy->id : null]);
        $data = PoliticaLiberacion::factory()->raw();

        return match ($action) {
            Action::POLITICA_LIBERACION_CREADA => fn () => $policies->create($admin, $data),
            Action::POLITICA_LIBERACION_ACTIVADA => fn () => $policies->state($admin, $policy->id, true),
            Action::POLITICA_LIBERACION_DESACTIVADA => fn () => $policies->state($admin, $policy->id, false),
            Action::POLITICA_ASIGNADA_ESPACIO => fn () => $policies->assign($admin, $space->getKey(), $policy->id),
            Action::POLITICA_RETIRADA_ESPACIO => fn () => $policies->assign($admin, $space->getKey(), null),
        };
    }

    protected function businessSnapshot(): array
    {
        $tables = ['usuarios', 'roles', 'roles_permisos', 'asignaciones_roles', 'sesiones_acceso', 'recursos_institucionales', 'espacios_academicos', 'bloqueos_espacio', 'pisos', 'tipos_espacio', 'caracteristicas_espacio', 'equipamientos_tecnologicos', 'tipos_equipamiento', 'sensores_iot', 'codigos_qr', 'politicas_liberacion'];
        $snapshot = [];
        foreach ($tables as $table) {
            $snapshot[$table] = DB::table($table)->get()->toJson();
        }

        return $snapshot;
    }
}
