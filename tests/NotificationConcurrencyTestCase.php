<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

abstract class NotificationConcurrencyTestCase extends NotificationTestCase
{
    private array $workers = [];

    protected string $prefix;

    private string $run;

    protected function setUp(): void
    {
        parent::setUp();
        $dir = base_path('.local/testing/concurrency');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $this->run = bin2hex(random_bytes(8));
        $this->prefix = $dir.DIRECTORY_SEPARATOR.$this->run;
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($this->workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop();
            }
        }
        foreach (glob($this->prefix.'.*') ?: [] as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    protected function waitFor(string $suffix): void
    {
        $deadline = microtime(true) + 15;
        while (! is_file($this->prefix.$suffix)) {
            if (microtime(true) > $deadline) {
                $this->fail('No se alcanzó la barrera '.$suffix);
            }
            usleep(10000);
        }
    }

    protected function start(array $input): Process
    {
        $slot = count($this->workers);
        $process = new Process([PHP_BINARY, base_path('tests/Support/concurrent-notifications.php')], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => '.local/testing/config.php'], json_encode(['time' => now()->toDateTimeString(), ...$input, 'run' => $this->run, 'slot' => $slot], JSON_THROW_ON_ERROR), 30);
        $process->start();
        $this->workers[] = $process;
        $this->waitFor('.'.$slot.'.ready');

        return $process;
    }

    protected function workerResult(Process $process): array
    {
        $process->wait();
        $this->assertTrue($process->isSuccessful(), 'Worker: '.$process->getOutput());
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $result['transactions']);

        return $result;
    }

    protected function go(): void
    {
        file_put_contents($this->prefix.'.go', 'go');
    }

    protected function release(int $slot): void
    {
        file_put_contents($this->prefix.'.'.$slot.'.release', 'release');
    }
}
