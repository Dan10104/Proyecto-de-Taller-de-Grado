<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Models\SesionAcceso;
use App\Services\AccessSessionService;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SecurityTestCase;

class AuthenticationTest extends SecurityTestCase
{
    public function test_active_user_logs_in_with_regenerated_session_and_hashed_control_record(): void
    {
        $user = $this->user('Administrador');
        $this->get('/login');
        $before = session()->getId();
        $csrf = session()->token();
        $this->login($user);
        $id = session()->getId();
        $this->assertNotSame($before, $id);
        $this->assertNotSame($csrf, session()->token());
        $row = SesionAcceso::sole();
        $this->assertSame(hash('sha256', $id), $row->token_hash);
        $this->assertNotSame($id, $row->token_hash);
        $this->assertSame(64, strlen($row->token_hash));
        $this->assertSame($user->id, $row->usuario_id);
        $this->assertNull($row->revocada_en);
        $this->assertEqualsWithDelta(config('session.lifetime') * 60, $row->creada_en->diffInSeconds($row->expira_en), 1);
        $this->get('/inicio')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Home')->has('auth.roles', 1)->has('auth.permisos', count(PermissionCatalog::forRole('Administrador')))->missing('auth.usuario.password_hash'));
    }

    public static function invalidCredentials(): array
    {
        return [['wrong'], ['missing'], ['inactive']];
    }

    #[DataProvider('invalidCredentials')]
    public function test_invalid_logins_are_generic_and_create_no_session(string $kind): void
    {
        $user = $this->user();
        if ($kind === 'inactive') {
            $user->forceFill(['estado' => EstadoUsuario::INACTIVO])->save();
        }
        $this->post('/login', ['correo' => $kind === 'missing' ? 'ausente@example.test' : $user->correo, 'password' => $kind === 'wrong' ? bin2hex(random_bytes(20)) : $this->password])
            ->assertSessionHasErrors(['correo' => 'Las credenciales proporcionadas no son válidas.']);
        $this->assertGuest();
        $this->assertDatabaseCount('sesiones_acceso', 0);
    }

    public function test_login_rate_limit_is_temporary(): void
    {
        $user = $this->user();
        $input = ['correo' => $user->correo, 'password' => bin2hex(random_bytes(20))];
        for ($i = 0; $i < config('security.login_attempts'); $i++) {
            $this->post('/login', $input)->assertSessionHasErrors('correo');
        }
        $this->post('/login', ['correo' => $user->correo, 'password' => $this->password])->assertSessionHasErrors(['correo' => 'Demasiados intentos. Espere un minuto e inténtelo nuevamente.']);
        $this->travel(config('security.login_decay_seconds') + 1)->seconds();
        $this->login($user);
    }

    public function test_logout_revokes_record_and_invalidates_session_and_csrf(): void
    {
        $this->login($this->user());
        $old = session()->getId();
        $csrf = session()->token();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertNotNull(SesionAcceso::sole()->revocada_en);
        $this->assertNotSame($old, session()->getId());
        $this->assertNotSame($csrf, session()->token());
        Auth::forgetGuards();
        $this->withCookie(config('session.cookie'), $old)->get('/inicio')->assertRedirect('/login');
    }

    public function test_logout_can_be_repeated_when_already_anonymous(): void
    {
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public static function invalidSessions(): array
    {
        return [['expired'], ['revoked'], ['missing'], ['other_user'], ['inactive']];
    }

    #[DataProvider('invalidSessions')]
    public function test_control_record_is_required_for_every_protected_request(string $kind): void
    {
        $user = $this->user('Administrador');
        $this->login($user);
        $row = SesionAcceso::sole();
        match ($kind) {
            'expired' => $row->forceFill(['creada_en' => now()->subHours(3), 'expira_en' => now()])->save(),
            'revoked' => $row->forceFill(['revocada_en' => now()])->save(),
            'missing' => $row->delete(),
            'other_user' => $row->forceFill(['usuario_id' => $this->user()->id])->save(),
            'inactive' => $user->forceFill(['estado' => EstadoUsuario::INACTIVO])->save(),
        };
        $this->get('/usuarios')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_valid_activity_renews_expiry_using_config_without_changing_creation(): void
    {
        $this->login($this->user());
        $row = SesionAcceso::sole();
        $created = $row->creada_en;
        $this->travel(2)->minutes();
        $this->get('/inicio')->assertOk();
        $row->refresh();
        $this->assertTrue($created->equalTo($row->creada_en));
        $this->assertEqualsWithDelta(config('session.lifetime') * 60, now()->diffInSeconds($row->expira_en), 1);
    }

    public function test_multiple_sessions_are_independent(): void
    {
        $user = $this->user();
        $this->login($user);
        $other = (new SesionAcceso)->forceFill(['usuario_id' => $user->id, 'token_hash' => hash('sha256', random_bytes(32)), 'creada_en' => now(), 'expira_en' => now()->addMinutes(config('session.lifetime'))]);
        $other->save();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertNull($other->fresh()->revocada_en);
    }

    public function test_session_registration_failure_does_not_leave_partial_authentication(): void
    {
        $user = $this->user();
        $mock = \Mockery::mock(AccessSessionService::class)->makePartial();
        $mock->shouldReceive('register')->andThrow(new \RuntimeException('Synthetic storage failure'));
        $this->app->instance(AccessSessionService::class, $mock);
        $this->post('/login', ['correo' => $user->correo, 'password' => $this->password])->assertStatus(503);
        $this->assertGuest();
        $this->assertDatabaseCount('sesiones_acceso', 0);
    }

    public function test_anonymous_routes_require_authentication(): void
    {
        foreach (['/inicio', '/usuarios', '/usuarios/crear', '/roles', '/permisos'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_passwords_use_argon2id_and_are_hidden(): void
    {
        $user = $this->user();
        $this->assertSame('argon2id', password_get_info($user->password_hash)['algoName']);
        $this->assertTrue(Hash::check($this->password, $user->password_hash));
        $this->assertArrayNotHasKey('password_hash', $user->toArray());
    }

    public function test_failed_logout_invalidates_authentication_and_retry_revokes_original_record(): void
    {
        $this->login($this->user());
        $original = session()->getId();
        $service = new AccessSessionService;
        $failure = \Mockery::mock(AccessSessionService::class)->makePartial();
        $failure->shouldReceive('revoke')->once()->andThrow(new \RuntimeException('Synthetic unavailable database'));
        $this->app->instance(AccessSessionService::class, $failure);
        $this->post('/logout')->assertStatus(503);
        $this->assertGuest();
        $this->assertNotSame($original, session()->getId());
        $this->assertSame(hash('sha256', $original), session()->get('pending_logout_hash'));
        $this->assertNull(SesionAcceso::sole()->revocada_en);
        $this->app->instance(AccessSessionService::class, $service);
        $this->withCookie(config('session.cookie'), session()->getId());
        Auth::forgetGuards();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertNotNull(SesionAcceso::sole()->revocada_en);
        $this->assertNull(session()->get('pending_logout_hash'));
    }
}
