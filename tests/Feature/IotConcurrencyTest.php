<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\EventoOcupacion;
use App\Models\Permiso;
use App\Models\RecursoInstitucional;
use App\Models\Rol;
use App\Models\SensorIot;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IotTestCase;

class IotConcurrencyTest extends IotTestCase
{
    private array $workers = [];

    private string $prefix;

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
        foreach ($this->workers as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        foreach (glob($this->prefix.'.*') ?: [] as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    private function waitFor(string $suffix): void
    {
        $deadline = microtime(true) + 12;
        while (! is_file($this->prefix.$suffix)) {
            if (microtime(true) > $deadline) {
                $this->fail('No se alcanzó la barrera '.$suffix);
            }
            usleep(10000);
        }
    }

    private function start(array $input): Process
    {
        $slot = count($this->workers);
        $process = new Process([PHP_BINARY, base_path('tests/Support/concurrent-iot.php')], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => '.local/testing/config.php'], json_encode([...$input, 'run' => $this->run, 'slot' => $slot], JSON_THROW_ON_ERROR), 25);
        $process->start();
        $this->workers[] = $process;
        $this->waitFor('.'.$slot.'.ready');

        return $process;
    }

    private function workerResult(Process $process): array
    {
        $process->wait();
        $this->assertTrue($process->isSuccessful(), 'Worker: '.$process->getOutput());
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $result['transactions']);

        return $result;
    }

    private function go(): void
    {
        file_put_contents($this->prefix.'.go', 'go');
    }

    public static function histories(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('histories')]
    public function test_simultaneous_duplicate_sees_committed_writer_after_sensor_mutex(bool $history): void
    {
        $s = SensorIot::factory()->create();
        if ($history) {
            EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'fecha_evento' => now()->subDay()]);
        }
        $input = ['action' => 'ingest', 'sensor' => $s->id, 'raw' => $this->body($s)];
        $a = $this->start($input);
        $b = $this->start($input);
        $this->go();
        $ra = $this->workerResult($a);
        $rb = $this->workerResult($b);
        $this->assertNotSame($ra['connection'], $rb['connection']);
        $this->assertEqualsCanonicalizing(['accepted', 'duplicate'], [$ra['status'], $rb['status']]);
        $this->assertSame($ra['event_id'], $rb['event_id']);
        $this->assertDatabaseCount('eventos_ocupacion', $history ? 2 : 1);
        $this->assertTrue($s->fresh()->ultima_comunicacion->eq(EventoOcupacion::findOrFail($ra['event_id'])->fecha_recepcion));
    }

    public function test_distinct_events_for_one_sensor_both_commit(): void
    {
        $s = SensorIot::factory()->create();
        $a = $this->start(['action' => 'ingest', 'sensor' => $s->id, 'raw' => $this->body($s, ['value' => 1])]);
        $b = $this->start(['action' => 'ingest', 'sensor' => $s->id, 'raw' => $this->body($s, ['value' => 2])]);
        $this->go();
        $this->assertSame('accepted', $this->workerResult($a)['status']);
        $this->assertSame('accepted', $this->workerResult($b)['status']);
        $this->assertDatabaseCount('eventos_ocupacion', 2);
    }

    #[DataProvider('histories')]
    public function test_different_sensors_advance_with_compatible_space_locks_and_without_rbac(bool $sameSpace): void
    {
        $a = SensorIot::factory()->create();
        $b = SensorIot::factory()->create($sameSpace ? ['espacio_actual_id' => $a->espacio_actual_id] : []);
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $first = $this->start(['action' => 'ingest', 'sensor' => $a->id, 'raw' => $this->body($a), 'hold' => 'space']);
        $this->go();
        $this->waitFor('.0.held');
        $second = $this->start(['action' => 'ingest', 'sensor' => $b->id, 'raw' => $this->body($b)]);
        $this->assertSame('accepted', $this->workerResult($second)['status']);
        $this->assertTrue($first->isRunning());
        DB::commit();
        file_put_contents($this->prefix.'.0.release', 'release');
        $this->assertSame('accepted', $this->workerResult($first)['status']);
        $this->assertDatabaseCount('eventos_ocupacion', 2);
    }

    public static function changes(): array
    {
        return [['disable'], ['reassign']];
    }

    #[DataProvider('changes')]
    public function test_management_commits_first_and_waiting_ingestion_rechecks_state_and_context(string $action): void
    {
        $s = SensorIot::factory()->create();
        $other = EspacioAcademico::factory()->create();
        $raw = $this->body($s);
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $locked = SensorIot::whereKey($s->id)->lockForUpdate()->sole();
        $locked->forceFill($action === 'disable' ? ['estado' => 'DESHABILITADO'] : ['espacio_actual_id' => $other->recurso_id])->save();
        $p = $this->start(['action' => 'ingest', 'sensor' => $s->id, 'raw' => $raw]);
        $this->go();
        $this->waitFor('.0.sensor');
        DB::commit();
        $this->assertSame($action === 'disable' ? 'device_not_authorized' : 'space_context_mismatch', $this->workerResult($p)['status']);
        $this->assertDatabaseCount('eventos_ocupacion', 0);
        $this->assertNull($s->fresh()->ultima_comunicacion);
    }

    #[DataProvider('changes')]
    public function test_ingestion_commits_first_and_management_preserves_accepted_history(string $action): void
    {
        $s = SensorIot::factory()->create();
        $other = EspacioAcademico::factory()->create();
        $actor = $this->user('Administrador');
        $p = $this->start(['action' => 'ingest', 'sensor' => $s->id, 'raw' => $this->body($s), 'hold' => 'sensor']);
        $this->go();
        $this->waitFor('.0.held');
        $q = $this->start(['action' => $action, 'sensor' => $s->id, 'actor' => $actor->id, 'space' => $other->recurso_id]);
        $this->waitFor('.1.sensor');
        file_put_contents($this->prefix.'.0.release', 'release');
        $this->assertSame('accepted', $this->workerResult($p)['status']);
        $this->assertSame('ok', $this->workerResult($q)['status']);
        $this->assertSame($s->espacio_actual_id, EventoOcupacion::sole()->espacio_historico_id);
        $this->assertNotNull($s->fresh()->ultima_comunicacion);
    }

    public function test_space_code_change_during_shared_lock_wait_is_revalidated(): void
    {
        $s = SensorIot::factory()->create();
        $raw = $this->body($s);
        DB::beginTransaction();
        EspacioAcademico::whereKey($s->espacio_actual_id)->lockForUpdate()->sole();
        $r = RecursoInstitucional::whereKey($s->espacio_actual_id)->lockForUpdate()->sole();
        $r->codigo = 'CHANGED-SYNTHETIC';
        $r->save();
        $p = $this->start(['action' => 'ingest', 'sensor' => $s->id, 'raw' => $raw]);
        $this->go();
        $this->waitFor('.0.space');
        DB::commit();
        $this->assertSame('space_context_mismatch', $this->workerResult($p)['status']);
        $this->assertDatabaseCount('eventos_ocupacion', 0);
    }

    public function test_request_window_is_rechecked_after_real_lock_wait(): void
    {
        $s = SensorIot::factory()->create();
        $raw = $this->body($s);
        DB::beginTransaction();
        SensorIot::whereKey($s->id)->lockForUpdate()->sole();
        $p = $this->start(['action' => 'ingest', 'sensor' => $s->id, 'raw' => $raw, 'expire_after_lock' => true]);
        $this->go();
        $this->waitFor('.0.sensor');
        DB::commit();
        $this->assertSame('authentication_failed', $this->workerResult($p)['status']);
        $this->assertDatabaseCount('eventos_ocupacion', 0);
    }

    public function test_provision_reauthorizes_after_concurrent_permission_withdrawal(): void
    {
        $s = SensorIot::factory()->create();
        $actor = $this->user('Encargado de Laboratorio');
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        Rol::where('nombre', 'Encargado de Laboratorio')->sole()->permisos()->detach(Permiso::where('codigo', 'iot.sensores.gestionar')->sole());
        $p = $this->start(['action' => 'provision', 'sensor' => $s->id, 'actor' => $actor->id]);
        $this->go();
        $this->waitFor('.0.security');
        DB::commit();
        $this->assertSame('denied', $this->workerResult($p)['status']);
    }

    public function test_quota_is_atomic_across_two_workers(): void
    {
        $s = SensorIot::factory()->create();
        $input = ['action' => 'quota', 'sensor' => $s->id];
        $a = $this->start($input);
        $b = $this->start($input);
        $this->go();
        $ra = $this->workerResult($a);
        $rb = $this->workerResult($b);
        $this->assertNotSame($ra['connection'], $rb['connection']);
        $this->assertSame(120, $ra['accepted'] + $rb['accepted']);
    }
}
