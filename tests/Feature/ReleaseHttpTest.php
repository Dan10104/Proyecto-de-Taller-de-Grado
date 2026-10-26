<?php

namespace Tests\Feature;

use App\Models\AsignacionRol;
use App\Models\EspacioAcademico;
use App\Models\EvaluacionLiberacion;
use App\Models\EventoOcupacion;
use App\Models\Permiso;
use App\Models\PoliticaLiberacion;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\ValidacionQr;
use Database\Seeders\SecuritySeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SecurityTestCase;
use Tests\Support\EnforceCsrf;

class ReleaseHttpTest extends SecurityTestCase
{
    public function test_exact_four_permissions_roles_and_revocation_without_bypass(): void
    {
        $codes = ['liberacion.politicas.ver', 'liberacion.politicas.gestionar', 'liberacion.evaluaciones.ver_administrativas', 'liberacion.evaluaciones.ver_propias'];
        $this->assertEqualsCanonicalizing($codes, Permiso::where('codigo', 'like', 'liberacion.%')->pluck('codigo')->all());
        $this->assertSame(51, Permiso::count());
        foreach (['Administrador', 'Encargado de Laboratorio', 'Docente', 'Estudiante'] as $role) {
            $user = $this->user($role);
            foreach ($codes as $code) {
                $admin = in_array($role, ['Administrador', 'Encargado de Laboratorio']);
                $this->assertSame($code === 'liberacion.evaluaciones.ver_propias' ? ! $admin : $admin, $user->can($code));
            }
        }
        $role = Rol::where('nombre', 'Administrador')->sole();
        $permission = Permiso::where('codigo', $codes[1])->sole();
        $role->permisos()->detach($permission);
        $this->seed(SecuritySeeder::class);
        $this->assertFalse($this->user('Administrador')->can($codes[1]));
        Rol::where('nombre', 'Docente')->sole()->permisos()->attach($permission);
        $this->assertFalse($this->user('Docente')->can($codes[1]));
        $user = $this->user('Administrador');
        $this->grantRole($user, 'Docente');
        $this->assertFalse($user->can($codes[1])); // Eligibility and permission must be on the same role.
    }

    public function test_admin_creates_versions_views_assignments_and_cannot_edit_or_delete(): void
    {
        $this->login($this->user('Administrador'));
        $space = EspacioAcademico::factory()->create();
        $data = ['nombre' => 'Política sintética HTTP', 'version' => 1, 'tolerancia_min' => 5, 'requiere_qr' => true, 'usa_iot' => false, 'max_antiguedad_lectura_min' => null, 'modo' => 'ALERTA', 'activa' => true];
        $this->get('/liberacion/politicas/crear')->assertOk();
        $this->post('/liberacion/politicas', $data)->assertRedirect();
        $p = PoliticaLiberacion::sole();
        $this->get('/liberacion/politicas?q=HTTP')->assertInertia(fn (Assert $a) => $a->component('Release/Policies')->has('politicas.data', 1));
        $this->get('/liberacion/politicas/'.$p->id)->assertInertia(fn (Assert $a) => $a->component('Release/PolicyShow')->where('politica.version', 1));
        $this->get('/liberacion/politicas/crear?desde='.$p->id)->assertInertia(fn (Assert $a) => $a->where('origen.id', $p->id));
        $this->get('/liberacion/asignaciones?q='.$space->recurso->codigo)->assertInertia(fn (Assert $a) => $a->has('espacios.data', 1));
        $this->put('/liberacion/asignaciones/'.$space->recurso_id, ['politica_id' => $p->id])->assertRedirect();
        $this->assertSame($p->id, $space->fresh()->politica_liberacion_id);
        $this->post('/liberacion/politicas', [...$data, 'version' => 2, 'modo' => 'AUTOMATICA'])->assertRedirect();
        $this->assertSame('ALERTA', $p->fresh()->modo->value);
        $this->assertSame($p->id, $space->fresh()->politica_liberacion_id);
        $this->patch('/liberacion/politicas/'.$p->id.'/estado', ['activa' => false])->assertRedirect();
        $this->assertFalse($p->fresh()->activa);
        $this->put('/liberacion/politicas/'.$p->id, $data)->assertStatus(405);
        $this->delete('/liberacion/politicas/'.$p->id)->assertStatus(405);
        $this->post('/liberacion/evaluaciones')->assertStatus(405);
    }

    public static function owners(): array
    {
        return [['Docente'], ['Estudiante']];
    }

    #[DataProvider('owners')]
    public function test_own_scope_is_enforced_and_has_no_technical_or_foreign_identity_fields(string $role): void
    {
        $user = $this->user($role);
        $r = Reserva::factory()->aprobada()->create(['solicitante_id' => $user->id]);
        $e = EvaluacionLiberacion::factory()->create(['reserva_id' => $r->id]);
        $other = EvaluacionLiberacion::factory()->create();
        $event = EventoOcupacion::factory()->create();
        $validation = ValidacionQr::factory()->create(['reserva_id' => $r->id]);
        $e->eventos()->attach($event);
        $e->validaciones()->attach($validation);
        $this->login($user);
        $this->get('/liberacion/evaluaciones?solicitante_id='.$other->reserva->solicitante_id)->assertInertia(fn (Assert $a) => $a->component('Release/Evaluations')->where('alcance', 'propias')->has('evaluaciones.data', 1)->where('evaluaciones.data.0.id', $e->id));
        $response = $this->get('/liberacion/evaluaciones/'.$e->id);
        $response->assertInertia(fn (Assert $a) => $a->component('Release/EvaluationShow')->missing('validaciones')->missing('eventos')->missing('evaluacion.reserva.solicitante_id'));
        foreach ([$validation->hash_leido, $event->sensor->identificador] as $secretOrTechnical) {
            $this->assertStringNotContainsString($secretOrTechnical, $response->getContent());
        }
        $this->get('/liberacion/evaluaciones/'.$other->id)->assertNotFound();
        $this->get('/liberacion/evaluaciones?alcance=administrativas')->assertForbidden();
        $this->get('/liberacion/politicas')->assertForbidden();
        $this->get('/liberacion/asignaciones')->assertForbidden();
        $this->put('/liberacion/asignaciones/'.$r->espacio_id, ['politica_id' => null])->assertForbidden();
        $this->post('/liberacion/politicas', [])->assertForbidden();
    }

    public function test_administrative_detail_shows_only_safe_used_evidence_and_pagination(): void
    {
        $this->login($this->user('Encargado de Laboratorio'));
        $e = EvaluacionLiberacion::factory()->create();
        $q = ValidacionQr::factory()->create(['reserva_id' => $e->reserva_id]);
        $events = EventoOcupacion::factory()->count(21)->create();
        $e->validaciones()->attach($q);
        $e->eventos()->attach($events->pluck('id'));
        $this->get('/liberacion/evaluaciones/'.$e->id)->assertInertia(fn (Assert $a) => $a->has('validaciones.data', 1)->has('eventos.data', 20)->where('eventos.total', 21)->missing('validaciones.data.0.hash_leido')->missing('validaciones.data.0.codigo_qr_id')->missing('eventos.data.0.pivot'));
        $this->get('/liberacion/evaluaciones/'.$e->id.'?iot_page=2')->assertInertia(fn (Assert $a) => $a->has('eventos.data', 1));
        $this->get('/liberacion/evaluaciones?resultado=NO_DETERMINABLE&politica_id='.$e->politica_id)->assertInertia(fn (Assert $a) => $a->has('evaluaciones.data', 1));
        $this->get('/liberacion/evaluaciones?alcance=propias')->assertForbidden();
        $this->patch('/liberacion/evaluaciones/'.$e->id, [])->assertStatus(405);
        $this->delete('/liberacion/evaluaciones/'.$e->id)->assertStatus(405);
    }

    public function test_session_permission_revocation_and_csrf_apply_to_direct_urls(): void
    {
        $user = $this->user('Administrador');
        $this->login($user);
        $p = PoliticaLiberacion::factory()->create();
        $this->app->bind(PreventRequestForgery::class, EnforceCsrf::class);
        $this->patch('/liberacion/politicas/'.$p->id.'/estado', ['activa' => false])->assertStatus(419);
        $this->app->forgetInstance(PreventRequestForgery::class);
        $this->app->bind(PreventRequestForgery::class, PreventRequestForgery::class);
        Rol::where('nombre', 'Administrador')->sole()->permisos()->detach(Permiso::where('codigo', 'liberacion.politicas.gestionar')->sole());
        $this->patch('/liberacion/politicas/'.$p->id.'/estado', ['activa' => false])->assertForbidden();
        $this->assertTrue($p->fresh()->activa);
        AsignacionRol::where('usuario_id', $user->id)->update(['fecha_retiro' => now()]);
        $this->get('/liberacion/politicas')->assertForbidden();
        DB::table('sesiones_acceso')->where('usuario_id', $user->id)->update(['revocada_en' => now()]);
        $this->get('/liberacion/evaluaciones')->assertRedirect('/login');
    }
}
