<?php

namespace Tests\Feature;

use App\Models\AsignacionRol;
use App\Models\EvidenciaTrazabilidad;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\ReservationService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group('traceability')]
class TraceabilityConcurrencyTest extends TestCase
{
    private array $workers = [];

    private string $prefix;

    private string $run;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh --seed --force')->assertSuccessful();
        $this->run = bin2hex(random_bytes(8));
        $dir = base_path('.local/testing/concurrency');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $this->prefix = $dir.DIRECTORY_SEPARATOR.$this->run;
    }

    protected function tearDown(): void
    {
        foreach ($this->workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop();
            }
        }
        foreach (glob($this->prefix.'.*') ?: [] as $file) {
            unlink($file);
        }
        $this->artisan('migrate:fresh --seed --force')->assertSuccessful();
        parent::tearDown();
    }

    private function evidence(): EvidenciaTrazabilidad
    {
        $u = Usuario::factory()->create();
        (new AsignacionRol)->forceFill(['usuario_id' => $u->id, 'rol_id' => Rol::where('nombre', 'Administrador')->sole()->id, 'asignado_por_id' => $u->id, 'fecha_asignacion' => now()])->save();
        $r = Reserva::factory()->create();
        $started = hrtime(true);
        app(ReservationService::class)->approve($u, $r);
        $this->assertLessThanOrEqual(3.0, (hrtime(true) - $started) / 1e9, 'La evidencia local debe quedar preparada en <=3 segundos.');

        return EvidenciaTrazabilidad::where('registro_actividad_id', RegistroActividad::where('reserva_id', $r->id)->sole()->id)->sole();
    }

    private function start(string $action, int $id, bool $hold = false): Process
    {
        $slot = count($this->workers);
        $p = new Process([PHP_BINARY, base_path('tests/Support/concurrent-traceability.php')], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => '.local/testing/config.php'], json_encode(['action' => $action, 'id' => $id, 'hold' => $hold, 'slot' => $slot, 'run' => $this->run]), 30);
        $p->start();
        $this->workers[] = $p;
        $this->wait('.'.$slot.'.ready');

        return $p;
    }

    private function wait(string $suffix): void
    {
        $deadline = microtime(true) + 15;
        while (! is_file($this->prefix.$suffix)) {
            if (microtime(true) > $deadline) {
                $this->fail('Barrera no alcanzada: '.$suffix);
            } usleep(10000);
        }
    }

    private function workerResult(Process $p): array
    {
        $p->wait();
        $this->assertTrue($p->isSuccessful(), $p->getOutput());
        $result = json_decode($p->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $result['transactions']);

        return $result;
    }

    public function test_two_processes_create_one_local_evidence_after_a_crash(): void
    {
        $e = $this->evidence();
        $activityId = $e->registro_actividad_id;
        DB::table('evidencias_trazabilidad')->where('id', $e->id)->delete();
        $a = $this->start('ensure', $activityId);
        $b = $this->start('ensure', $activityId);
        file_put_contents($this->prefix.'.go', 'go');
        $ra = $this->workerResult($a);
        $rb = $this->workerResult($b);
        $this->assertSame('ok', $ra['status']);
        $this->assertSame('ok', $rb['status']);
        $this->assertSame($ra['result'], $rb['result']);
        $this->assertDatabaseCount('evidencias_trazabilidad', 1);
    }

    public function test_wallet_nonce_lock_serializes_different_evidences_and_releases_after_broadcast(): void
    {
        $one = $this->evidence();
        $two = $this->evidence();
        $a = $this->start('register', $one->id, true);
        file_put_contents($this->prefix.'.go', 'go');
        $this->wait('.broadcast');
        DB::transaction(function () use ($one) {
            $activity = $one->actividad;
            $this->assertNotNull(DB::selectOne('SELECT id FROM reservas WHERE id=? FOR UPDATE NOWAIT', [$activity->reserva_id]));
            $this->assertNotNull(DB::selectOne('SELECT recurso_id FROM espacios_academicos WHERE recurso_id=? FOR UPDATE NOWAIT', [$activity->recurso_id]));
        });
        $b = $this->start('register', $two->id);
        $rb = $this->workerResult($b);
        $this->assertSame('BUSY', $rb['status']);
        $this->assertSame(0, $rb['broadcasts']);
        file_put_contents($this->prefix.'.release', 'release');
        $this->assertSame(1, $this->workerResult($a)['broadcasts']);
        $retry = $this->workerResult($this->start('register', $two->id));
        $this->assertSame('ok', $retry['status']);
        $this->assertSame(1, $retry['broadcasts']);
        $this->assertSame(2, EvidenciaTrazabilidad::where('estado', 'REGISTRADA')->count());
    }

    public function test_verification_cannot_publish_stale_pending_while_registration_holds_evidence_lock(): void
    {
        $e = $this->evidence();
        $a = $this->start('register', $e->id, true);
        file_put_contents($this->prefix.'.go', 'go');
        $this->wait('.broadcast');
        $b = $this->start('verify', $e->id);
        $this->assertSame('BUSY', $this->workerResult($b)['status']);
        file_put_contents($this->prefix.'.release', 'release');
        $this->workerResult($a);
        $result = $this->workerResult($this->start('verify', $e->id));
        $this->assertSame('VALIDO', $result['result']);
        $this->assertSame('REGISTRADA', $e->fresh()->estado->value);
        $this->assertSame('VALIDO', $e->fresh()->resultado_verificacion->value);
    }
}
