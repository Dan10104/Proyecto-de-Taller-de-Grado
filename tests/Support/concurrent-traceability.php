<?php

use App\Services\Traceability\BlockchainDriver;
use App\Services\Traceability\EvidenceProcessor;
use App\Services\Traceability\LocalEvidenceService;
use App\Services\Traceability\TraceabilityUnavailable;
use App\Support\TestingDatabaseGuard;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeBlockchainDriver;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestingDatabaseGuard::check();
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! preg_match('/^[a-f0-9]{16}$/D', $input['run']) || ! in_array($input['slot'], [0, 1, 2], true)) {
    exit(2);
}
$prefix = base_path('.local/testing/concurrency/'.$input['run']);
$wait = static function ($file) {
    $deadline = microtime(true) + 15;
    while (! is_file($file)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('barrier');
        } usleep(10000);
    }
};
$driver = new FakeBlockchainDriver;
if (is_file($prefix.'.proofs')) {
    $driver->proofs = json_decode(file_get_contents($prefix.'.proofs'), true, flags: JSON_THROW_ON_ERROR);
}
if ($input['hold'] ?? false) {
    $driver->onRegister = static function () use ($prefix, $wait) {
        if (DB::transactionLevel() !== 0) {
            throw new RuntimeException('network-inside-transaction');
        }
        file_put_contents($prefix.'.broadcast', 'ready');
        $wait($prefix.'.release');
    };
}
app()->instance(BlockchainDriver::class, $driver);
config(['blockchain.enabled' => true, 'blockchain.contract_address' => '0x'.str_repeat('a', 40), 'blockchain.private_key' => '']);
file_put_contents($prefix.'.'.$input['slot'].'.ready', 'ready');
$wait($prefix.'.go');
try {
    $result = match ($input['action']) {
        'ensure' => app(LocalEvidenceService::class)->ensure($input['id'])->id,
        'register' => app(EvidenceProcessor::class)->register($input['id']),
        'verify' => app(EvidenceProcessor::class)->verify($input['id']),
    };
    if ($driver->broadcasts) {
        file_put_contents($prefix.'.proofs', json_encode($driver->proofs));
    }
    echo json_encode(['status' => 'ok', 'result' => $result, 'broadcasts' => $driver->broadcasts, 'transactions' => DB::transactionLevel()]);
} catch (TraceabilityUnavailable $error) {
    echo json_encode(['status' => $error->reason, 'broadcasts' => $driver->broadcasts, 'transactions' => DB::transactionLevel()]);
} catch (Throwable) {
    echo '{"status":"unexpected"}';
    exit(1);
}
