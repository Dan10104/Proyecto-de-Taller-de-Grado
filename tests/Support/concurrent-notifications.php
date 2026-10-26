<?php

use App\Exceptions\ReleaseDeferredException;
use App\Jobs\GenerateNotifications;
use App\Models\Prestamo;
use App\Models\Usuario;
use App\Services\LoanService;
use App\Services\Notifications\NotificationGenerationService;
use App\Services\Notifications\NotificationReadService;
use App\Services\Notifications\NotificationReconciler;
use App\Services\Notifications\OverdueLoanService;
use App\Services\ReleaseEvaluationService;
use App\Support\TestingDatabaseGuard;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

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
Carbon::setTestNow(Carbon::parse($input['time'], 'America/La_Paz'));
CarbonImmutable::setTestNow(CarbonImmutable::parse($input['time'], 'America/La_Paz'));
config(['notifications.events_from' => '2000-01-01 00:00:00', 'blockchain.enabled' => false]);
Bus::fake([GenerateNotifications::class]);
DB::statement('SET SESSION innodb_lock_wait_timeout=1');
$connection = DB::selectOne('SELECT CONNECTION_ID() id')->id;
$wait = static function ($file) {
    $end = microtime(true) + 15;
    while (! is_file($file)) {
        if (microtime(true) > $end) {
            throw new RuntimeException('barrier');
        }usleep(10000);
    }
};
$attempts = 0;
$held = false;
DB::connection()->beforeExecuting(function ($sql) use ($prefix, &$attempts) {
    if (str_contains($sql, 'from `registros_actividad`') && str_contains($sql, 'for update')) {
        $attempts++;
        file_put_contents($prefix.'.locking', 'attempt');
    }
    if (str_contains($sql, 'from `prestamos`') && str_contains($sql, 'for update')) {
        file_put_contents($prefix.'.loan', 'attempt');
    }
    if (str_starts_with($sql, 'update `notificaciones`')) {
        file_put_contents($prefix.'.reading', 'attempt');
    }
});
DB::listen(function ($q) use ($input, $prefix, $wait, &$held) {
    if ($held || ! isset($input['hold'])) {
        return;
    }
    $match = match ($input['hold']) {
        'activity' => str_contains($q->sql, 'from `registros_actividad`') && str_contains($q->sql, 'for update'),
        'snapshot' => str_contains($q->sql, 'from `notificaciones`'),
        'insert' => str_starts_with($q->sql, 'insert into `notificaciones`'),
        'event' => str_starts_with($q->sql, 'insert into `registros_actividad`'),
        'return' => str_starts_with($q->sql, 'update `prestamos`'),
        'read' => str_starts_with($q->sql, 'update `notificaciones`'),
        default => false,
    };
    if ($match) {
        $held = true;
        file_put_contents($prefix.'.held', 'held');
        $wait($prefix.'.release');
    }
});
file_put_contents($prefix.'.ready', 'ready');
$wait($directory.DIRECTORY_SEPARATOR.$input['run'].'.go');
try {
    $result = match ($input['action']) {
        'generate' => app(NotificationGenerationService::class)->generate($input['id']),
        'reconcile' => app(NotificationReconciler::class)->run()['creados'],
        'read' => app(NotificationReadService::class)->mark(Usuario::findOrFail($input['actor']), $input['id'])->leida_en->toDateTimeString(),
        'overdue' => app(OverdueLoanService::class)->detect($input['id']),
        'return' => app(LoanService::class)->receive(Usuario::findOrFail($input['actor']), Prestamo::findOrFail($input['id']), ['condicion' => 'ADECUADA'])->estado->value,
        'release' => app(ReleaseEvaluationService::class)->evaluate($input['id'])?->resultado->value,
        default => throw new RuntimeException('action'),
    };
    $status = 'ok';
} catch (QueryException $e) {
    $status = 'database_'.$e->errorInfo[1];
    $result = null;
} catch (ReleaseDeferredException) {
    $status = 'deferred';
    $result = null;
} catch (Throwable $e) {
    $status = 'unexpected:'.get_class($e);
    $result = null;
}
echo json_encode(['status' => $status, 'result' => $result, 'connection' => $connection, 'attempts' => $attempts, 'transactions' => DB::transactionLevel()]);
if (str_starts_with($status, 'unexpected')) {
    exit(1);
}
