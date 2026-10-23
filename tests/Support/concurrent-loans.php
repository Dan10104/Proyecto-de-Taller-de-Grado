<?php

use App\Exceptions\EquipmentConflictException;
use App\Models\IncidenciaMantenimiento;
use App\Models\Prestamo;
use App\Models\Usuario;
use App\Services\IncidentMaintenanceService;
use App\Services\LoanService;
use App\Support\TestingDatabaseGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

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
    if (str_contains($sql, 'for update') && str_contains($sql, 'from `equipamientos_tecnologicos`')) {
        file_put_contents($prefix.'.'.$slot.'.locking', 'locking');
    }if (str_contains($sql, 'for update') && str_contains($sql, 'from `roles`')) {
        file_put_contents($prefix.'.'.$slot.'.security', 'security');
    }
});

try {
    $actor = Usuario::findOrFail($input['actor']);
    $service = app(LoanService::class);
    $incidents = app(IncidentMaintenanceService::class);
    $result = match ($input['action']) {
        'create' => $service->create($actor, $input['data']),
        'approve' => $service->approve($actor, Prestamo::findOrFail($input['target'])),
        'reject' => $service->reject($actor, Prestamo::findOrFail($input['target']), 'Rechazo concurrente'),
        'deliver' => $service->deliver($actor, Prestamo::findOrFail($input['target']), ['condicion_entrega' => 'Condición sintética', 'equipo_verificado' => true, 'receptor_verificado' => true]),
        'receive' => $service->receive($actor, Prestamo::findOrFail($input['target']), $input['data']),
        'review' => $service->confirmReview($actor, Prestamo::findOrFail($input['target']), $input['data']),
        'open' => $incidents->open($actor, $input['data']),
        'close' => $incidents->close($actor, IncidenciaMantenimiento::findOrFail($input['target']), $input['data']),
    };
    $status = 'ok';
} catch (EquipmentConflictException) {
    $status = 'conflict';
} catch (AuthorizationException) {
    $status = 'denied';
} catch (Throwable $e) {
    echo json_encode(['status' => 'unexpected', 'class' => get_class($e)]);
    exit(1);
}
echo json_encode(['status' => $status, 'connection' => (int) $connection, 'transactions' => DB::transactionLevel()]);
