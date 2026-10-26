<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SensorIot;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReleaseConcurrencyTestCase;

class ActivityConcurrencyTest extends ReleaseConcurrencyTestCase
{
    public static function transitions(): array
    {
        return [['space', 'ESPACIO_ESTADO_CAMBIADO'], ['withdraw', 'ROL_RETIRADO'], ['sensor', 'SENSOR_DESHABILITADO']];
    }

    #[DataProvider('transitions')]
    public function test_real_mysql_concurrent_repeated_transition_produces_one_activity(string $kind, string $action): void
    {
        $admin = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        $sensor = SensorIot::factory()->create(['espacio_actual_id' => $space->getKey()]);
        $user = $this->user('Estudiante');
        $role = Rol::where('nombre', 'Estudiante')->sole();
        $input = match ($kind) {
            'space' => ['action' => 'audit_space_state', 'space' => $space->getKey(), 'hold' => 'space'],
            'withdraw' => ['action' => 'audit_withdraw', 'user' => $user->id, 'role' => $role->id],
            'sensor' => ['action' => 'disable', 'sensor' => $sensor->id, 'hold' => 'sensor'],
        };
        $input['actor'] = $admin->id;
        $first = $this->start($input);
        $this->go();
        if (isset($input['hold'])) {
            $this->waitFor('.0.held');
        }
        unset($input['hold']);
        $second = $this->start($input);
        $this->waitFor('.1.security');
        $this->release(0);
        $a = $this->workerResult($first);
        $b = $this->workerResult($second);
        $this->assertNotSame($a['connection'], $b['connection']);
        $this->assertSame('ok', $a['status']);
        $this->assertSame('ok', $b['status']);
        $this->assertSame(1, DB::table('registros_actividad')->where('accion', $action)->where('resultado', 'EXITO')->count());
        $this->assertSame(1, DB::table('registros_actividad')->count());
    }

    public function test_permission_revoked_while_waiting_on_management_lock_prevents_mutation_and_audit(): void
    {
        $admin = $this->user('Administrador');
        $space = EspacioAcademico::factory()->create();
        DB::beginTransaction();
        $role = Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $worker = $this->start(['action' => 'audit_space_state', 'space' => $space->getKey(), 'actor' => $admin->id]);
        $this->go();
        $this->waitFor('.0.security');
        $role->permisos()->detach(Permiso::where('codigo', 'espacios.cambiar_estado')->value('id'));
        DB::commit();
        $this->assertSame('denied', $this->workerResult($worker)['status']);
        $this->assertSame('DISPONIBLE', $space->fresh()->estado->value);
        $this->assertDatabaseCount('registros_actividad', 0);
    }
}
