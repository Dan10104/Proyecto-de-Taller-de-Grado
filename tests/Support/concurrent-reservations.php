<?php

use App\Exceptions\ReservationConflictException;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\ReservationService;
use App\Support\TestingDatabaseGuard;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
    if (str_contains($sql, 'for update') && str_contains($sql, 'from `espacios_academicos`')) {
        file_put_contents($prefix.'.'.$slot.'.locking', 'locking');
    }if (str_contains($sql, 'for update') && str_contains($sql, 'from `roles`')) {
        file_put_contents($prefix.'.'.$slot.'.security', 'security');
    }
});
if (isset($input['clock'])) {
    Carbon::setTestNow($input['clock']);
    CarbonImmutable::setTestNow($input['clock']);
}
if (isset($input['clock_after_lock'])) {
    DB::listen(function ($q) use ($input) {
        if (str_contains($q->sql, 'for update') && str_contains($q->sql, 'from `espacios_academicos`')) {
            Carbon::setTestNow($input['clock_after_lock']);
            CarbonImmutable::setTestNow($input['clock_after_lock']);
        }
    });
}
try {
    $service = app(ReservationService::class);
    $actor = Usuario::findOrFail($input['actor']);
    if ($input['action'] === 'create') {
        $result = $service->create($actor, $input['data']);
    } else {
        $target = Reserva::findOrFail($input['target']);
        $result = match ($input['action']) {
            'approve' => $service->approve($actor, $target),'reject' => $service->reject($actor, $target),'cancel' => $service->cancel($actor, $target)
        };
    }
    $status = 'ok';
} catch (ReservationConflictException) {
    $status = 'conflict';
} catch (AuthorizationException) {
    $status = 'denied';
} catch (Throwable $e) {
    echo json_encode(['status' => 'unexpected', 'class' => get_class($e)]);
    exit(1);
}
echo json_encode(['status' => $status, 'connection' => (int) $connection, 'transactions' => DB::transactionLevel()]);
