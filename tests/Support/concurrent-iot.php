<?php

use App\Exceptions\IotException;
use App\Models\SensorIot;
use App\Models\Usuario;
use App\Services\IotIngestionService;
use App\Services\IotManagementService;
use App\Services\IotRateLimiter;
use App\Support\TestingDatabaseGuard;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestingDatabaseGuard::check();
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$directory = realpath(base_path('.local/testing/concurrency'));
if (! $directory || ! preg_match('/\A[a-f0-9]{16}\z/', $input['run']) || ! in_array($input['slot'], [0, 1], true)) {
    exit(2);
}
$prefix = $directory.DIRECTORY_SEPARATOR.$input['run'];
$slot = $input['slot'];
$wait = function (string $path) {
    $deadline = microtime(true) + 12;
    while (! is_file($path)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Test barrier timeout');
        }
        usleep(10000);
    }
};
$connection = DB::selectOne('SELECT CONNECTION_ID() id')->id;
DB::connection()->beforeExecuting(function ($sql) use ($prefix, $slot) {
    foreach (['sensores_iot' => 'sensor', 'espacios_academicos' => 'space', 'recursos_institucionales' => 'resource', 'roles' => 'security'] as $table => $suffix) {
        if (str_contains($sql, 'from `'.$table.'`') && (str_contains($sql, 'for update') || str_contains($sql, 'share'))) {
            file_put_contents($prefix.'.'.$slot.'.'.$suffix, 'attempt');
        }
    }
});
DB::listen(function ($query) use ($input, $prefix, $slot, $wait) {
    $table = ($input['hold'] ?? '') === 'space' ? 'recursos_institucionales' : 'sensores_iot';
    if (isset($input['hold']) && str_contains($query->sql, 'from `'.$table.'`') && (str_contains($query->sql, 'for update') || str_contains($query->sql, 'share'))) {
        file_put_contents($prefix.'.'.$slot.'.held', 'held');
        $wait($prefix.'.'.$slot.'.release');
    }
    if (! empty($input['expire_after_lock']) && str_contains($query->sql, 'from `sensores_iot`') && str_contains($query->sql, 'for update')) {
        Carbon::setTestNow(now()->addSeconds(301));
    }
});
file_put_contents($prefix.'.'.$slot.'.ready', 'ready');
$wait($prefix.'.go');
try {
    if ($input['action'] === 'quota') {
        $accepted = 0;
        for ($i = 0; $i < 80; $i++) {
            try {
                app(IotRateLimiter::class)->consume('sensor:'.$input['sensor'], 120);
                $accepted++;
            } catch (IotException $error) {
                if ($error->status !== 429) {
                    throw $error;
                }
            }
        }
        $result = ['status' => 'quota', 'accepted' => $accepted];
    } else {
        $sensor = SensorIot::findOrFail($input['sensor']);
        if ($input['action'] === 'ingest') {
            $stamp = (string) now()->timestamp;
            $raw = $input['raw'];
            $key = hash_hmac('sha256', 'sensor:'.$sensor->identificador, hex2bin(config('iot.master_secret')), true);
            $signature = hash_hmac('sha256', "POST\n/api/iot/events\n".$sensor->identificador."\n".$stamp."\n".hash('sha256', $raw), $key);
            $request = new Request;
            $request->headers->replace(['X-IoT-Sensor' => $sensor->identificador, 'X-IoT-Timestamp' => $stamp, 'X-IoT-Signature' => $signature]);
            $response = app(IotIngestionService::class)->receive($request, $raw);
            $result = ['status' => $response['result'], 'event_id' => $response['event_id']];
        } else {
            $actor = Usuario::findOrFail($input['actor']);
            $service = app(IotManagementService::class);
            match ($input['action']) {
                'disable' => $service->state($actor, $sensor, 'DESHABILITADO'),
                'reassign' => $service->save($actor, $sensor, ['tipo' => 'Sintético', 'espacio_actual_id' => $input['space']]),
                'provision' => $service->provision($actor, $sensor),
            };
            $result = ['status' => 'ok'];
        }
    }
} catch (IotException $error) {
    $result = ['status' => $error->result];
} catch (AuthorizationException) {
    $result = ['status' => 'denied'];
} catch (Throwable $error) {
    echo json_encode(['status' => 'unexpected', 'class' => $error::class]);
    exit(1);
}
unset($input, $key, $signature, $raw, $response);
echo json_encode([...$result, 'connection' => (int) $connection, 'transactions' => DB::transactionLevel()]);
