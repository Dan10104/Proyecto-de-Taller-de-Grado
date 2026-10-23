<?php

namespace Tests\Feature;

use App\Exceptions\SessionStorageException;
use App\Models\SesionAcceso;
use App\Models\Usuario;
use App\Support\CheckedFileSessionHandler;
use App\Support\TestingDatabaseGuard;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Tests\SecurityTestCase;
use Tests\Support\EnforceCsrf;

class SecurityBoundariesTest extends SecurityTestCase
{
    public function test_csrf_rejects_login_logout_and_authenticated_writes_without_token(): void
    {
        $user = $this->user('Administrador');
        $this->app->bind(PreventRequestForgery::class, EnforceCsrf::class);
        $this->post('/login', ['correo' => $user->correo, 'password' => $this->password])->assertStatus(419);
        $this->assertGuest();
        $this->post('/logout')->assertStatus(419);
        $this->get('/login');
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post('/login', ['correo' => $user->correo, 'password' => $this->password, '_token' => session()->token()])->assertRedirect('/inicio');
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post('/usuarios', $this->userData())->assertStatus(419);
        $this->assertDatabaseCount('usuarios', 1);
        $this->post('/logout', ['_token' => session()->token()])->assertRedirect('/login');
    }

    public function test_session_cookie_has_http_only_and_same_site_and_private_responses(): void
    {
        $response = $this->get('/login')->assertOk();
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_testing_guard_rejects_development_configuration_before_any_query(): void
    {
        $before = DB::selectOne('SELECT DATABASE() AS name')->name;
        config(['database.connections.unsafe' => [...config('database.connections.mysql'), 'database' => 'espacio_u']]);
        try {
            TestingDatabaseGuard::check('unsafe');
            $this->fail('La guarda debió rechazar el destino');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('rechazada', $e->getMessage());
        }
        $this->assertSame('espacio_u_testing', $before);
    }

    public function test_testing_account_cannot_access_normal_development_database(): void
    {
        try {
            DB::select('SELECT * FROM espacio_u.usuarios');
            $this->fail('La cuenta de testing no debe acceder a desarrollo');
        } catch (QueryException $e) {
            $this->assertSame(1142, $e->errorInfo[1]);
        }
    }

    public function test_store_failure_is_not_silently_accepted(): void
    {
        $files = \Mockery::mock(Filesystem::class);
        $files->shouldReceive('put')->andReturn(false);
        $handler = new CheckedFileSessionHandler($files, base_path('.local/testing/sessions'), config('session.lifetime'));
        $this->expectException(SessionStorageException::class);
        $handler->write(bin2hex(random_bytes(20)), 'synthetic');
    }

    public function test_destruction_failure_is_not_silently_accepted(): void
    {
        $files = \Mockery::mock(Filesystem::class);
        $files->shouldReceive('delete')->andReturn(false);
        $files->shouldReceive('isFile')->andReturn(true);
        $handler = new CheckedFileSessionHandler($files, base_path('.local/testing/sessions'), config('session.lifetime'));
        $this->expectException(SessionStorageException::class);
        $handler->destroy(bin2hex(random_bytes(20)));
    }

    public function test_session_payload_and_passwords_never_appear_in_inertia(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $row = SesionAcceso::sole();
        foreach (['/inicio', '/usuarios', '/usuarios/'.$admin->id] as $url) {
            $response = $this->get($url)->assertOk();
            foreach (['password_hash', 'token_hash', $this->password, $admin->password_hash, $row->token_hash, session()->getId()] as $secret) {
                $response->assertDontSee($secret, false);
            }
        }
    }

    public function test_bootstrap_requires_local_secret_and_does_not_replace_existing_account(): void
    {
        $this->artisan('security:bootstrap-admin --from-env')->assertFailed();
        $this->assertDatabaseCount('usuarios', 0);
        config(['security.bootstrap_email' => 'initial-'.bin2hex(random_bytes(4)).'@example.test', 'security.bootstrap_password' => $this->password]);
        $this->artisan('security:bootstrap-admin --from-env')->assertSuccessful();
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertSame(1, Usuario::sole()->rolesActivos()->count());
        $hash = Usuario::sole()->password_hash;
        $this->artisan('security:bootstrap-admin --from-env')->assertFailed();
        $this->assertSame($hash, Usuario::sole()->password_hash);
    }

    public function test_store_failure_during_protected_response_revokes_access_and_expires_cookie(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        $handler = \Mockery::mock(session()->getHandler())->makePartial();
        $handler->shouldReceive('write')->andThrow(new SessionStorageException('Session storage unavailable'));
        session()->setHandler($handler);
        $response = $this->get('/inicio')->assertStatus(503);
        $this->assertNotNull(SesionAcceso::sole()->revocada_en);
        $response->assertCookieExpired(config('session.cookie'));
        $response->assertDontSee($admin->password_hash, false);
    }

    public function test_malformed_email_types_are_validation_errors_not_server_errors(): void
    {
        foreach ([['invalid'], 123, true] as $email) {
            $this->post('/login', ['correo' => $email, 'password' => $this->password])->assertSessionHasErrors('correo');
        }
        $admin = $this->user('Administrador');
        $this->login($admin);
        $this->post('/usuarios', $this->userData(['correo' => ['invalid']]))->assertSessionHasErrors('correo');
        $this->assertDatabaseCount('usuarios', 1);
    }
}
