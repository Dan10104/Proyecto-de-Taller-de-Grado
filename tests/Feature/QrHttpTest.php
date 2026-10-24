<?php

namespace Tests\Feature;

use App\Models\CodigoQr;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SesionAcceso;
use App\Models\ValidacionQr;
use Database\Seeders\SecuritySeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\QrTestCase;
use Tests\Support\EnforceCsrf;

class QrHttpTest extends QrTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_permission_matrix_seed_is_exact_idempotent_and_preserves_revocation(): void
    {
        $permissions = ['qr.gestionar', 'qr.validar_uso_espacio', 'qr.validar_operacion'];
        $this->assertSame($permissions, Permiso::where('codigo', 'like', 'qr.%')->orderBy('id')->pluck('codigo')->all());
        foreach (['Administrador' => [true, true, true], 'Encargado de Laboratorio' => [true, true, true], 'Docente' => [false, true, false], 'Estudiante' => [false, true, false]] as $role => $grants) {
            $u = $this->user($role);
            foreach ($permissions as $i => $p) {
                $this->assertSame($grants[$i], $u->can($p), $role.':'.$p);
            }
        }
        $role = Rol::where('nombre', 'Encargado de Laboratorio')->sole();
        $role->permisos()->detach(Permiso::where('codigo', 'qr.gestionar')->sole());
        $this->seed(SecuritySeeder::class);
        $this->assertFalse($role->permisos()->where('codigo', 'qr.gestionar')->exists());
        $this->assertSame(3, Permiso::where('codigo', 'like', 'qr.%')->count());
    }

    public function test_management_generation_rotation_revocation_and_private_metadata(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $p = $this->loan();
        $this->login($a);
        $this->get('/qr')->assertOk();
        foreach (['recurso' => $r->espacio_id, 'reserva' => $r->id, 'prestamo' => $p->id] as $type => $id) {
            $url = "/qr/$type/$id";
            $first = $this->postJson($url)->assertCreated()->assertJsonStructure(['id', 'svg']);
            $this->assertStringContainsString('<svg', $first->json('svg'));
            $this->assertStringContainsString('no-store', $first->headers->get('Cache-Control'));
            $old = $first->json('id');
            $second = $this->postJson($url)->assertCreated();
            $this->assertFalse(CodigoQr::findOrFail($old)->activo);
            $this->assertSame(1, CodigoQr::where($type.'_id', $id)->where('activo', true)->count());
            $this->get($url)->assertInertia(fn (Assert $v) => $v->component('Qr/Manage')->has('codigos.data', 2)->missing('codigos.data.0.token_hash')->missing('svg')->missing('token'));
            $this->postJson($url.'/'.$second->json('id').'/desactivar')->assertOk();
            $this->assertFalse(CodigoQr::findOrFail($second->json('id'))->activo);
        }
        $this->assertDatabaseCount('validaciones_qr', 0);
    }

    public function test_owner_post_exposes_only_safe_evidence_and_server_context(): void
    {
        $u = $this->user('Docente');
        $r = $this->reservation($u);
        $t = $this->token();
        $this->code($t, ['reserva_id' => $r->id]);
        $this->login($u);
        $response = $this->postJson('/reservas/'.$r->id.'/qr', ['qr_token' => $t])->assertOk()->assertJsonPath('validacion.resultado', 'VALIDA');
        $this->assertFalse(str_contains($response->getContent(), $t));
        $this->assertFalse(str_contains($response->getContent(), hash('sha256', $t)));
        $v = ValidacionQr::sole();
        $this->assertSame($u->id, $v->validado_por_id);
        $this->assertSame($r->espacio_id, $v->recurso_esperado_id);
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->get('/reservas/'.$r->id.'/qr')->assertOk()->assertInertia(fn (Assert $v) => $v->component('Qr/Space')->has('validaciones.data', 1)->missing('validaciones.data.0.hash_leido')->missing('validaciones.data.0.codigo_qr_id'));
    }

    public function test_utf8_and_whitespace_are_hashed_exactly_and_unknown_attempts_commit(): void
    {
        $u = $this->user('Estudiante');
        $r = $this->reservation($u);
        $this->login($u);
        foreach ([' ', "\t\n", ' QR inválido ', str_repeat('x', 1024), $this->token().'='] as $payload) {
            $this->postJson('/reservas/'.$r->id.'/qr', ['qr_token' => $payload])->assertStatus(409)->assertJsonPath('validacion.resultado', 'INVALIDA');
            $v = ValidacionQr::latest('id')->first();
            $this->assertTrue(hash_equals(hash('sha256', $payload), $v->hash_leido));
            $this->assertNull($v->codigo_qr_id);
            $this->assertNotEmpty($v->motivo_rechazo);
        }
        $this->assertDatabaseCount('validaciones_qr', 5);
        $this->assertDatabaseCount('codigos_qr', 0);
    }

    public function test_admission_and_forged_fields_never_create_attempt_or_flash_raw_input(): void
    {
        $u = $this->user('Docente');
        $r = $this->reservation($u);
        $this->login($u);
        $url = '/reservas/'.$r->id.'/qr';
        foreach ([[], ['qr_token' => null], ['qr_token' => ''], ['qr_token' => []], ['qr_token' => 123], ['qr_token' => str_repeat('x', 1025)]] as $data) {
            $this->postJson($url, $data)->assertUnprocessable();
        }
        foreach (['validacion_id', 'codigo_qr_id', 'hash_leido', 'token_hash', 'recurso_esperado_id', 'validado_por_id', 'fecha_hora', 'resultado', 'tipo_operacion', 'entrega_id', 'devolucion_id', 'reserva_id', 'prestamo_id'] as $field) {
            $this->postJson($url, ['qr_token' => $this->token(), $field => 1])->assertUnprocessable();
        }
        $t = $this->token();
        $this->post($url, ['qr_token' => $t, 'validacion_id' => 1])->assertRedirect();
        $this->assertFalse(str_contains(serialize(session()->all()), $t));
        $this->postJson($url.'?qr_token=x', ['qr_token' => $t])->assertUnprocessable();
        $this->assertDatabaseCount('validaciones_qr', 0);
    }

    public function test_foreign_context_low_roles_revoked_permission_and_inactive_actor_deny(): void
    {
        $u = $this->user('Docente');
        $r = $this->reservation();
        $t = $this->token();
        $this->login($u);
        $this->get('/qr')->assertForbidden();
        $this->postJson('/qr/recurso/'.$r->espacio_id)->assertForbidden();
        $this->get('/reservas/'.$r->id.'/qr')->assertForbidden();
        $this->postJson('/reservas/'.$r->id.'/qr', ['qr_token' => $t])->assertForbidden();
        $own = $this->reservation($u);
        Rol::where('nombre', 'Docente')->sole()->permisos()->detach(Permiso::where('codigo', 'qr.validar_uso_espacio')->sole());
        $this->postJson('/reservas/'.$own->id.'/qr', ['qr_token' => $t])->assertForbidden();
        $u->forceFill(['estado' => 'INACTIVO'])->save();
        $this->postJson('/reservas/'.$own->id.'/qr', ['qr_token' => $t])->assertUnauthorized();
        $this->assertDatabaseCount('validaciones_qr', 0);
    }

    public function test_qr_delivery_invalid_cannot_fall_back_then_correct_delivery_and_return_correlate(): void
    {
        $a = $this->user('Administrador');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $t = $this->token();
        $this->code($t, ['prestamo_id' => $p->id]);
        $this->login($a);
        $url = '/prestamos/'.$p->id;
        $this->postJson($url.'/qr/entregar', $this->deliveryData(['qr_token' => $this->token()]))->assertStatus(409);
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->postJson($url.'/entregar', $this->deliveryData(['qr_token' => $t]))->assertUnprocessable();
        $this->postJson($url.'/qr/entregar', $this->deliveryData(['qr_token' => $t]))->assertOk()->assertJsonPath('validacion.resultado', 'VALIDA');
        $this->postJson($url.'/qr/devolver', ['qr_token' => $t, 'condicion' => 'ADECUADA'])->assertOk();
        $this->getJson($url.'/qr/validaciones')->assertOk()->assertJsonPath('validaciones.total', 3)->assertJsonMissingPath('validaciones.data.0.hash_leido');
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
        $this->assertDatabaseCount('validaciones_qr', 3);
    }

    public function test_csrf_and_revoked_session_block_qr_posts(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $p = $this->loan();
        $this->login($a);
        $this->app->bind(PreventRequestForgery::class, EnforceCsrf::class);
        foreach (['/qr/recurso/'.$r->espacio_id, '/qr/recurso/'.$r->espacio_id.'/1/desactivar', '/reservas/'.$r->id.'/qr', '/prestamos/'.$p->id.'/qr/entregar', '/prestamos/'.$p->id.'/qr/devolver'] as $url) {
            $this->postJson($url)->assertStatus(419);
        }
        SesionAcceso::where('usuario_id', $a->id)->update(['revocada_en' => now()]);
        $this->postJson('/qr/recurso/'.$r->espacio_id, ['_token' => session()->token()])->assertUnauthorized();
        $this->assertDatabaseCount('codigos_qr', 0);
        $this->assertDatabaseCount('validaciones_qr', 0);
    }

    public function test_unexpected_exception_never_logs_its_message_or_payload(): void
    {
        $u = $this->user('Docente');
        $r = $this->reservation($u);
        $t = $this->token();
        $this->code($t, ['reserva_id' => $r->id]);
        $this->login($u);
        Log::spy();
        ValidacionQr::creating(fn () => throw new \RuntimeException($t));
        try {
            $response = $this->postJson('/reservas/'.$r->id.'/qr', ['qr_token' => $t])->assertStatus(500);
        } finally {
            ValidacionQr::flushEventListeners();
        }
        $this->assertFalse(str_contains($response->getContent(), $t));
        $this->assertFalse(str_contains(serialize(session()->all()), $t));
        Log::shouldHaveReceived('error')->once()->with('Operación QR no disponible.', ['exception_class' => \RuntimeException::class]);
        $this->assertDatabaseCount('validaciones_qr', 0);
    }

    public function test_thirty_http_validations_per_minute_keep_consistent_results_without_state_mutation(): void
    {
        $u = $this->user('Docente');
        $r = $this->reservation($u);
        $token = $this->token();
        $this->code($token, ['reserva_id' => $r->id]);
        $this->login($u);
        $start = microtime(true);
        $max = 0;
        for ($i = 0; $i < 30; $i++) {
            $lap = microtime(true);
            $this->postJson('/reservas/'.$r->id.'/qr', ['qr_token' => $token])->assertOk()->assertJsonPath('validacion.resultado', 'VALIDA');
            $max = max($max, microtime(true) - $lap);
        }
        $total = microtime(true) - $start;
        $this->assertLessThan(60, $total);
        $this->assertLessThan(3, $max);
        $this->assertDatabaseCount('validaciones_qr', 30);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        fwrite(STDOUT, sprintf("\nQR rendimiento HTTP MySQL: 30 solicitudes %.4fs; máximo %.4fs.\n", $total, $max));
    }
}
