<?php

namespace Tests;

use App\Http\RawIotRequest;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\SensorIot;
use App\Models\Usuario;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

abstract class IotTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh --seed --force')->assertSuccessful();
        config(['iot.rate_limit_per_minute' => 10000, 'iot.origin_rate_limit_per_minute' => 10000]);
        Cache::store('file')->flush();
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        $this->travelBack();
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    protected function user(string $role): Usuario
    {
        $u = Usuario::factory()->create();
        (new AsignacionRol)->forceFill(['usuario_id' => $u->id, 'rol_id' => Rol::where('nombre', $role)->sole()->id, 'asignado_por_id' => $u->id, 'fecha_asignacion' => now()])->save();

        return $u;
    }

    protected function body(SensorIot $sensor, array $extra = []): string
    {
        return json_encode([...['sensor_identifier' => $sensor->identificador, 'space_code' => $sensor->espacioActual->recurso->codigo, 'type' => 'PRESENCIA', 'occurred_at' => now()->startOfSecond()->toIso8601String()], ...$extra], JSON_THROW_ON_ERROR);
    }

    protected function signed(SensorIot $sensor, string $raw, array $headers = [], string $uri = '/api/iot/events', string $method = 'POST'): TestResponse
    {
        $timestamp = is_array($headers['X-IoT-Timestamp'] ?? null) ? (string) now()->timestamp : (string) ($headers['X-IoT-Timestamp'] ?? now()->timestamp);
        // Independent reference implementation: do not sign using production helpers.
        $key = hash_hmac('sha256', 'sensor:'.$sensor->identificador, hex2bin(config('iot.master_secret')), true);
        $signature = hash_hmac('sha256', "POST\n/api/iot/events\n".$sensor->identificador."\n".$timestamp."\n".hash('sha256', $raw), $key);
        $request = new RawIotRequest([], [], [], [], [], ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri, 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => 80, 'REMOTE_ADDR' => '127.0.0.1'], $raw);
        $request->headers->replace(['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-IoT-Sensor' => $sensor->identificador, 'X-IoT-Timestamp' => $timestamp, 'X-IoT-Signature' => $signature, ...$headers]);
        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return TestResponse::fromBaseResponse($response, $request);
    }
}
