<?php

use App\Exceptions\IotException;
use App\Exceptions\ReleaseDeferredException;
use App\Exceptions\ReservationConflictException;
use App\Models\Reserva;
use App\Models\SensorIot;
use App\Models\Usuario;
use App\Services\IotIngestionService;
use App\Services\IotManagementService;
use App\Services\QrValidationService;
use App\Services\ReleaseEvaluationService;
use App\Services\ReleasePolicyManagementService;
use App\Services\ReservationService;
use App\Support\TestingDatabaseGuard;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestingDatabaseGuard::check();
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$directory = realpath(base_path('.local/testing/concurrency'));
if (! $directory || ! preg_match('/\A[a-f0-9]{16}\z/', $input['run']) || ! in_array($input['slot'], range(0, 9), true)) {
    exit(2);
}
$prefix = $directory.DIRECTORY_SEPARATOR.$input['run'].'.'.$input['slot'];
$clock = function (string $time) {
    Carbon::setTestNow(Carbon::parse($time, 'America/La_Paz'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse($time, 'America/La_Paz'));
};
$clock($input['time']);
$wait = function (string $file) {
    $deadline = microtime(true) + 15;
    while (! is_file($file)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Test barrier timeout');
        }
        usleep(10000);
    }
};
$connection = DB::selectOne('SELECT CONNECTION_ID() id')->id;
$attempts = 0;
$held = false;
DB::connection()->beforeExecuting(function ($sql) use ($prefix, &$attempts) {
    $sql = strtolower($sql);
    foreach (['reservas' => 'reserve', 'espacios_academicos' => 'space', 'politicas_liberacion' => 'policy', 'sensores_iot' => 'sensor', 'roles' => 'security'] as $table => $name) {
        if (str_contains($sql, 'from `'.$table.'`') && (str_contains($sql, 'for update') || str_contains($sql, 'share'))) {
            file_put_contents($prefix.'.'.$name, 'attempt');
            if ($name === 'reserve' && str_contains($sql, 'nowait')) {
                $attempts++;
            }
        }
    }
});
DB::listen(function ($query) use ($input, $prefix, $wait, $clock, &$held) {
    if ($held || ! isset($input['hold'])) {
        return;
    }
    $sql = strtolower($query->sql);
    $needle = match ($input['hold']) {
        'reserve' => 'from `reservas`', 'space' => 'from `espacios_academicos`', 'sensor' => 'from `sensores_iot`', 'policy' => 'from `politicas_liberacion`',
        'evaluation' => 'insert into `evaluaciones_liberacion`', 'transition' => 'update `reservas`', default => 'no-match',
    };
    if (str_contains($sql, $needle) && (str_contains($sql, 'for update') || str_contains($sql, 'share') || in_array($input['hold'], ['evaluation', 'transition'], true))) {
        $held = true;
        file_put_contents($prefix.'.held', 'held');
        $wait($prefix.'.release');
        if (isset($input['after_time'])) {
            $clock($input['after_time']);
        }
    }
});
file_put_contents($prefix.'.ready', 'ready');
$wait($directory.DIRECTORY_SEPARATOR.$input['run'].'.go');
try {
    $action = $input['action'];
    $actor = isset($input['actor']) ? Usuario::findOrFail($input['actor']) : null;
    if ($action === 'evaluate') {
        $e = app(ReleaseEvaluationService::class)->evaluate($input['reservation']);
        $status = $e ? $e->resultado->value : 'omitted';
    } elseif ($action === 'qr') {
        $status = app(QrValidationService::class)->useSpace($actor, Reserva::findOrFail($input['reservation']), $input['token'])->resultado->value;
    } elseif (in_array($action, ['cancel', 'approve'])) {
        $result = app(ReservationService::class)->$action($actor, Reserva::findOrFail($input['reservation']));
        $status = $result->estado->value;
    } elseif ($action === 'assign') {
        app(ReleasePolicyManagementService::class)->assign($actor, $input['space'], $input['policy']);
        $status = 'ok';
    } elseif ($action === 'policy_state') {
        app(ReleasePolicyManagementService::class)->state($actor, $input['policy'], $input['active']);
        $status = 'ok';
    } elseif ($action === 'policy_create') {
        app(ReleasePolicyManagementService::class)->create($actor, $input['data']);
        $status = 'ok';
    } elseif ($action === 'ingest') {
        $sensor = SensorIot::findOrFail($input['sensor']);
        $raw = json_encode(['sensor_identifier' => $sensor->identificador, 'space_code' => $sensor->espacioActual->recurso->codigo, 'type' => 'PRESENCIA', 'occurred_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR);
        $stamp = (string) now()->timestamp;
        $key = hash_hmac('sha256', 'sensor:'.$sensor->identificador, hex2bin(config('iot.master_secret')), true);
        $signature = hash_hmac('sha256', "POST\n/api/iot/events\n".$sensor->identificador."\n".$stamp."\n".hash('sha256', $raw), $key);
        $request = new Request;
        $request->headers->replace(['X-IoT-Sensor' => $sensor->identificador, 'X-IoT-Timestamp' => $stamp, 'X-IoT-Signature' => $signature]);
        $status = app(IotIngestionService::class)->receive($request, $raw)['result'];
    } else {
        $service = app(IotManagementService::class);
        if ($action === 'sensor_create') {
            $service->save($actor, null, ['identificador' => 'SYN-RELEASE-NEW', 'tipo' => 'Sintético', 'espacio_actual_id' => $input['space']]);
        } else {
            $sensor = SensorIot::findOrFail($input['sensor']);
            if (in_array($action, ['enable', 'disable'])) {
                $service->state($actor, $sensor, $action === 'enable' ? 'HABILITADO' : 'DESHABILITADO');
            } else {
                $service->save($actor, $sensor, ['tipo' => 'Sintético', 'espacio_actual_id' => $input['space']]);
            }
        }
        $status = 'ok';
    }
} catch (ReleaseDeferredException) {
    $status = 'deferred';
} catch (ReservationConflictException) {
    $status = 'conflict';
} catch (ValidationException) {
    $status = 'invalid';
} catch (AuthorizationException) {
    $status = 'denied';
} catch (IotException $e) {
    $status = $e->result;
} catch (HttpException $e) {
    $status = 'http-'.$e->getStatusCode();
} catch (Throwable $e) {
    echo json_encode(['status' => 'unexpected', 'class' => $e::class]);
    exit(1);
}
unset($input, $key, $signature, $raw, $result);
echo json_encode(['status' => $status, 'connection' => (int) $connection, 'attempts' => $attempts, 'transactions' => DB::transactionLevel()]);
