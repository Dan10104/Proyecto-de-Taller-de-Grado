<?php

use App\Enums\TipoOperacionQr;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Models\ValidacionQr;
use App\Services\QrManagementService;
use App\Services\QrValidationService;
use App\Support\TestingDatabaseGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestingDatabaseGuard::check();
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$directory = realpath(base_path('.local/testing/concurrency'));
if (! $directory || ! preg_match('/^[a-f0-9]{16}$/', $input['run']) || ! in_array($input['slot'], [0, 1], true)) {
    exit(2);
}
$prefix = $directory.DIRECTORY_SEPARATOR.$input['run'];
$slot = $input['slot'];
$wait = function (string $file) {
    $deadline = microtime(true) + 12;
    while (! is_file($file)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Test barrier timeout');
        }usleep(10000);
    }
};
file_put_contents($prefix.'.'.$slot.'.ready', 'ready');
$wait($prefix.'.go');
$connection = DB::selectOne('SELECT CONNECTION_ID() id')->id;
// Test instrumentation announces the lock attempt before executing SQL. It never changes production services.
DB::connection()->beforeExecuting(function ($sql) use ($prefix, $slot) {
    if (str_contains($sql, 'for update') && str_contains($sql, 'from `usuarios`')) {
        file_put_contents($prefix.'.'.$slot.'.user', 'user');
    }
    if (str_contains($sql, 'for update') && str_contains($sql, 'from `sesiones_acceso`')) {
        file_put_contents($prefix.'.'.$slot.'.session', 'session');
    }
    if (str_contains($sql, 'for update') && (str_contains($sql, 'from `equipamientos_tecnologicos`') || str_contains($sql, 'from `espacios_academicos`'))) {
        file_put_contents($prefix.'.'.$slot.'.locking', 'locking');
    }if (str_contains($sql, 'for update') && str_contains($sql, 'from `codigos_qr`')) {
        file_put_contents($prefix.'.'.$slot.'.qr', 'qr');
    }
    if (str_contains($sql, 'for update') && str_contains($sql, 'from `roles`')) {
        file_put_contents($prefix.'.'.$slot.'.security', 'security');
    }
});

try {
    $actor = Usuario::findOrFail($input['actor']);
    $management = app(QrManagementService::class);
    $validation = app(QrValidationService::class);
    $result = match ($input['action']) {
        'issue' => $management->issue($actor, $input['type'], $input['target']),
        'deactivate' => $management->deactivate($actor, $input['type'], $input['target'], $input['code']),
        'space' => $validation->useSpace($actor, Reserva::findOrFail($input['target']), $input['token'], $input['session_hash'] ?? null),
        'deliver' => $validation->operation($actor, Prestamo::findOrFail($input['target']), TipoOperacionQr::ENTREGA, $input['token'], ['condicion_entrega' => 'Inspección sintética', 'equipo_verificado' => true, 'receptor_verificado' => true]),
        'receive' => $validation->operation($actor, Prestamo::findOrFail($input['target']), TipoOperacionQr::DEVOLUCION, $input['token'], ['condicion' => $input['condition'], 'descripcion_condicion' => 'Inspección sintética']),
    };
    $status = $result instanceof ValidacionQr ? $result->resultado->value : 'ok';
    unset($input,$result);
} catch (AuthorizationException) {
    $status = 'denied';
} catch (HttpException $e) {
    $status = $e->getStatusCode() === 403 ? 'denied' : 'unexpected';
} catch (Throwable $e) {
    echo json_encode(['status' => 'unexpected', 'class' => $e::class]);
    exit(1);
}
echo json_encode(['status' => $status, 'connection' => (int) $connection, 'transactions' => DB::transactionLevel()]);
