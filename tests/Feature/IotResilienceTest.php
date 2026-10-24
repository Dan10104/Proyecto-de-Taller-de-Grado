<?php

namespace Tests\Feature;

use App\Models\EventoOcupacion;
use App\Models\SensorIot;
use App\Services\IotMonitoringService;
use Illuminate\Database\DeadlockException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IotTestCase;

class IotResilienceTest extends IotTestCase
{
    public static function failures(): array
    {
        return [['ordinary', 1, 503], ['deadlock', 1, 201], ['deadlock', 3, 503]];
    }

    #[DataProvider('failures')]
    public function test_failure_between_insert_and_communication_rolls_back_and_retry_is_bounded(string $kind, int $failures, int $status): void
    {
        $s = SensorIot::factory()->create();
        $attempts = 0;
        DB::connection()->beforeExecuting(function ($sql) use (&$attempts, $kind, $failures) {
            if (str_starts_with($sql, 'update `sensores_iot`') && str_contains($sql, 'ultima_comunicacion')) {
                $attempts++;
                if ($attempts <= $failures) {
                    throw $kind === 'deadlock' ? new DeadlockException('Deadlock found when trying to get lock') : new \RuntimeException('Synthetic persistence failure');
                }
            }
        });
        $raw = $this->body($s);
        $this->signed($s, $raw)->assertStatus($status);
        $this->assertSame($status === 201 ? 2 : $failures, $attempts);
        $this->assertDatabaseCount('eventos_ocupacion', $status === 201 ? 1 : 0);
        $this->assertSame(0, DB::transactionLevel());
        if ($status === 503) {
            $this->assertNull($s->fresh()->ultima_comunicacion);
        } else {
            $this->assertTrue($s->fresh()->ultima_comunicacion->eq(EventoOcupacion::sole()->fecha_recepcion));
            $this->signed($s, $raw)->assertOk()->assertJsonPath('result', 'duplicate');
        }
    }

    public function test_preexisting_snapshot_transaction_is_refused(): void
    {
        $s = SensorIot::factory()->create();
        DB::beginTransaction();
        EventoOcupacion::count();
        $this->signed($s, $this->body($s))->assertStatus(503);
        DB::rollBack();
        $this->assertDatabaseCount('eventos_ocupacion', 0);
    }

    public function test_120_distinct_events_under_a_minute_with_history_and_monitor_visible_under_five_seconds(): void
    {
        config(['iot.rate_limit_per_minute' => 120]);
        $sensors = SensorIot::factory()->count(2)->create();
        $history = [];
        foreach ($sensors as $sensor) {
            for ($i = 0; $i < 1000; $i++) {
                $history[] = ['sensor_id' => $sensor->id, 'espacio_historico_id' => $sensor->espacio_actual_id, 'tipo' => 'PRESENCIA', 'valor' => null, 'fecha_evento' => now()->subDays(10)->subSeconds($i), 'fecha_recepcion' => now()->subDays(10)];
            }
        }
        foreach (array_chunk($history, 200) as $batch) {
            DB::table('eventos_ocupacion')->insert($batch);
        }
        $start = microtime(true);
        for ($i = 0; $i < 120; $i++) {
            $sensor = $sensors[$i % 2];
            $this->signed($sensor, $this->body($sensor, ['value' => $i]))->assertCreated();
        }
        $elapsed = microtime(true) - $start;
        $queryStart = microtime(true);
        $readings = app(IotMonitoringService::class)->current($sensors->pluck('espacio_actual_id')->all());
        $queryMs = (microtime(true) - $queryStart) * 1000;
        $this->assertLessThan(60, $elapsed);
        $this->assertLessThan(2000, $queryMs); // 3s UI polling + <2s measured query budget.
        $this->assertCount(2, $readings);
        foreach ($readings as $rows) {
            $this->assertCount(1, $rows);
        }
        $this->assertDatabaseCount('eventos_ocupacion', 2120);
        if (! is_dir(base_path('.local/testing/iot'))) {
            mkdir(base_path('.local/testing/iot'), 0700, true);
        }
        file_put_contents(base_path('.local/testing/iot/performance.json'), json_encode(['accepted' => 120, 'sensors' => 2, 'history' => 2000, 'ingestion_seconds' => round($elapsed, 3), 'monitor_query_ms' => round($queryMs, 3), 'poll_ms' => 3000, 'database' => 'espacio_u_testing'], JSON_PRETTY_PRINT));
    }
}
