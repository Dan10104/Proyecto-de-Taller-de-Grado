<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\EventoOcupacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SensorIot;
use App\Services\IotManagementService;
use Database\Seeders\SecuritySeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\SecurityTestCase;
use Tests\Support\EnforceCsrf;

class IotManagementTest extends SecurityTestCase
{
    public function test_permissions_are_exact_idempotent_eligible_and_preserve_revocation(): void
    {
        $permissions = ['iot.sensores.ver', 'iot.sensores.gestionar', 'iot.monitoreo.ver'];
        $this->assertEqualsCanonicalizing($permissions, Permiso::where('codigo', 'like', 'iot.%')->pluck('codigo')->all());
        $this->assertSame(52, Permiso::count());
        foreach (['Administrador' => 48, 'Encargado de Laboratorio' => 33, 'Docente' => 12, 'Estudiante' => 12] as $role => $count) {
            $user = $this->user($role);
            $this->assertSame($count, Rol::where('nombre', $role)->sole()->permisos()->count());
            foreach ($permissions as $permission) {
                $this->assertSame(in_array($role, ['Administrador', 'Encargado de Laboratorio']), $user->can($permission));
            }
        }
        $role = Rol::where('nombre', 'Administrador')->sole();
        $role->permisos()->detach(Permiso::where('codigo', 'iot.sensores.gestionar')->sole());
        $this->seed(SecuritySeeder::class);
        $this->assertFalse($this->user('Administrador')->can('iot.sensores.gestionar'));
        $docent = Rol::where('nombre', 'Docente')->sole();
        $docent->permisos()->attach(Permiso::where('codigo', 'iot.sensores.gestionar')->sole());
        $this->assertFalse($this->user('Docente')->can('iot.sensores.gestionar'));
    }

    public function test_administrator_creates_edits_reassociates_and_changes_state_without_changing_identifier_or_history(): void
    {
        $this->login($this->user('Administrador'));
        $a = EspacioAcademico::factory()->create();
        $b = EspacioAcademico::factory()->create();
        $data = ['identificador' => 'SYN-MANAGE-001', 'tipo' => 'Sensor sintético', 'espacio_actual_id' => $a->recurso_id];
        $this->get('/iot/sensores/crear')->assertOk();
        $this->post('/iot/sensores', $data)->assertRedirect();
        $s = SensorIot::sole();
        $this->assertSame('HABILITADO', $s->estado->value);
        $this->assertNull($s->ultima_comunicacion);
        $this->post('/iot/sensores', $data)->assertSessionHasErrors('identificador');
        $this->put('/iot/sensores/'.$s->id, $data)->assertSessionHasErrors('identificador');
        EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'espacio_historico_id' => $a->recurso_id]);
        $this->put('/iot/sensores/'.$s->id, ['tipo' => 'Tipo actualizado', 'espacio_actual_id' => $b->recurso_id])->assertRedirect();
        $this->assertSame($b->recurso_id, $s->fresh()->espacio_actual_id);
        $this->assertSame($a->recurso_id, EventoOcupacion::sole()->espacio_historico_id);
        $this->patch('/iot/sensores/'.$s->id.'/estado', ['estado' => 'DESHABILITADO'])->assertRedirect();
        $this->assertSame('DESHABILITADO', $s->fresh()->estado->value);
        $this->assertNull($s->fresh()->ultima_comunicacion);
        $this->delete('/iot/sensores/'.$s->id)->assertStatus(405);
        $this->get('/iot/sensores?q=SYN-MANAGE&estado=DESHABILITADO')->assertInertia(fn (Assert $v) => $v->component('Iot/Sensors')->has('sensores.data', 1));
    }

    public function test_identifier_profile_is_not_trimmed_or_normalized_and_space_must_exist(): void
    {
        $this->login($this->user('Administrador'));
        $space = EspacioAcademico::factory()->create();
        foreach (['lowercase', ' SYN-1', 'SYN-1 ', '_SYN', 'SYN.1', 'SÉN-1', str_repeat('A', 121)] as $identifier) {
            $this->post('/iot/sensores', ['identificador' => $identifier, 'tipo' => 'Sintético', 'espacio_actual_id' => $space->recurso_id])->assertSessionHasErrors('identificador');
        }
        $this->post('/iot/sensores', ['identificador' => 'VALID-1', 'tipo' => 'Sintético', 'espacio_actual_id' => 999999])->assertSessionHasErrors('espacio_actual_id');
        $this->assertDatabaseCount('sensores_iot', 0);
    }

    public function test_provision_is_explicit_no_store_repeatable_and_absent_from_normal_props(): void
    {
        $u = $this->user('Encargado de Laboratorio');
        $this->login($u);
        $this->withCredentials();
        $s = SensorIot::factory()->create();
        $url = '/iot/sensores/'.$s->id;
        $response = $this->postJson($url.'/credencial')->assertOk();
        $key = $response->json('credential');
        $this->assertTrue(is_string($key) && (bool) preg_match('/\A[0-9a-f]{64}\z/', $key));
        $expected = hash_hmac('sha256', 'sensor:'.$s->identificador, hex2bin(config('iot.master_secret')));
        $this->assertTrue(hash_equals($expected, $key));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $again = $this->postJson($url.'/credencial')->assertOk()->json('credential');
        $this->assertTrue(hash_equals($key, $again));
        $this->get($url)->assertInertia(fn (Assert $v) => $v->component('Iot/Show')->missing('credential')->missing('sensor.key')->missing('sensor.master_secret'));
        $this->assertNull($s->fresh()->ultima_comunicacion);
        app(IotManagementService::class)->state($u, $s, 'DESHABILITADO');
        $this->postJson($url.'/credencial')->assertForbidden();
        $this->app->instance(PreventRequestForgery::class, new EnforceCsrf($this->app, $this->app['encrypter']));
        $this->postJson($url.'/credencial')->assertStatus(419);
    }

    public function test_direct_urls_deny_docent_student_and_revoked_permissions(): void
    {
        $s = SensorIot::factory()->create();
        foreach (['Docente', 'Estudiante', 'Administrador', 'Encargado de Laboratorio'] as $roleName) {
            $u = $this->user($roleName);
            if (in_array($roleName, ['Administrador', 'Encargado de Laboratorio'])) {
                Rol::where('nombre', $roleName)->sole()->permisos()->detach(Permiso::where('codigo', 'like', 'iot.%')->pluck('id'));
            }
            $this->login($u);
            foreach (['/iot/sensores', '/iot/sensores/crear', '/iot/sensores/'.$s->id, '/iot/sensores/'.$s->id.'/editar', '/iot/monitoreo'] as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post('/iot/sensores/'.$s->id.'/credencial')->assertForbidden();
            $this->patch('/iot/sensores/'.$s->id.'/estado', ['estado' => 'DESHABILITADO'])->assertForbidden();
        }
        $this->assertSame('HABILITADO', $s->fresh()->estado->value);
    }
}
