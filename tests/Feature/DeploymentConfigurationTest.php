<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustDeploymentProxies;
use App\Support\DeploymentConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeploymentConfigurationTest extends TestCase
{
    private function validLocal(): void
    {
        config([
            'notifications.events_from' => '2026-10-26 00:00:00',
            'deployment.target' => 'local-container', 'app.env' => 'production', 'app.debug' => false,
            'app.url' => 'http://127.0.0.1:8088', 'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'database.connections.mysql.url' => null, 'database.connections.mysql.host' => 'db',
            'database.connections.mysql.database' => 'espacio_u_container',
            'database.connections.mysql.username' => 'espacio_u_app',
            'database.connections.mysql.password' => bin2hex(random_bytes(24)),
            'session.driver' => 'database', 'session.block_store' => 'database',
            'session.secure' => false, 'session.http_only' => true, 'session.same_site' => 'lax',
            'cache.default' => 'database', 'queue.default' => 'database', 'queue.effects_connection' => 'database',
            'iot.rate_store' => 'database', 'queue.connections.database.retry_after' => 120,
            'logging.default' => 'stderr', 'logging.channels.emergency.path' => 'php://stderr',
        ]);
    }

    public function test_local_container_configuration_accepts_disabled_integrations_without_external_credentials(): void
    {
        $this->validLocal();
        config(['chatbot.enabled' => false, 'chatbot.api_key' => '', 'blockchain.enabled' => false, 'blockchain.rpc_url' => '']);
        DB::shouldReceive('connection')->never();
        $this->assertSame([], app(DeploymentConfiguration::class)->errors());
        $this->artisan('deployment:check')->assertSuccessful();
    }

    public function test_cloud_requires_secure_url_cookie_and_native_socket(): void
    {
        $this->validLocal();
        config(['deployment.target' => 'cloud-run']);
        $this->assertEqualsCanonicalizing(['https_url', 'secure_cookie', 'cloud_sql_socket'], app(DeploymentConfiguration::class)->errors());
        config(['app.url' => 'https://espacio-u.example.test', 'session.secure' => true, 'database.connections.mysql.unix_socket' => '/cloudsql/example-project:southamerica-west1:espacio-u']);
        $this->assertSame([], app(DeploymentConfiguration::class)->errors());
    }

    public static function unsafeSettings(): array
    {
        return [
            ['app.debug', true, 'production_mode'], ['app.key', 'invalid', 'application_key'],
            ['database.connections.mysql.username', 'root', 'database_identity'],
            ['database.connections.mysql.database', 'espacio_u_testing', 'database_name'],
            ['session.driver', 'file', 'session_driver'], ['cache.default', 'file', 'cache_default'],
            ['session.block_store', 'file', 'session_block_store'], ['iot.rate_store', 'file', 'iot_rate_store'],
            ['queue.effects_connection', 'deferred', 'queue_effects_connection'],
            ['app.url', 'http://public.example.test', 'local_loopback_url'],
            ['queue.connections.database.retry_after', 60, 'queue_retry_window'],
        ];
    }

    #[DataProvider('unsafeSettings')]
    public function test_unsafe_runtime_configuration_is_rejected_without_echoing_values(string $key, mixed $value, string $code): void
    {
        $this->validLocal();
        config([$key => $value]);
        $this->assertContains($code, app(DeploymentConfiguration::class)->errors());
        $this->artisan('deployment:check')->expectsOutputToContain($code)->assertFailed();
    }

    public function test_forwarded_https_is_trusted_only_on_cloud_boundary_and_forwarded_host_is_ignored(): void
    {
        foreach (['local-container' => false, 'cloud-run' => true] as $target => $secure) {
            config(['deployment.target' => $target]);
            $request = Request::create('http://app.example.test/login', 'GET', server: [
                'REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_PORT' => '443', 'HTTP_X_FORWARDED_HOST' => 'attacker.example.test',
            ]);
            (new TrustDeploymentProxies)->handle($request, function (Request $request) use ($secure) {
                $this->assertSame($secure, $request->isSecure());
                $this->assertSame('app.example.test', $request->getHost());

                return response('ok');
            });
        }
        Request::setTrustedProxies([], 0);
    }

    public function test_proxy_is_processed_before_iot_transport_boundary(): void
    {
        config(['deployment.target' => 'cloud-run', 'iot.rate_store' => 'array']);
        $this->app->instance('env', 'production');
        try {
            $this->withHeaders(['X-Forwarded-Proto' => 'https'])->postJson('http://localhost/api/iot/events', [])
                ->assertStatus(503)->assertJsonPath('result', 'temporarily_unavailable');
            config(['deployment.target' => 'local-container']);
            $this->withHeaders(['X-Forwarded-Proto' => 'https'])->postJson('http://localhost/api/iot/events', [])
                ->assertStatus(403)->assertJsonPath('result', 'secure_transport_required');
        } finally {
            $this->app->instance('env', 'testing');
            Request::setTrustedProxies([], 0);
        }
    }

    public function test_health_does_not_depend_on_database_or_optional_integrations(): void
    {
        config(['chatbot.enabled' => false, 'blockchain.enabled' => false]);
        DB::shouldReceive('connection')->never();
        $this->get('/up')->assertOk();
    }
}
