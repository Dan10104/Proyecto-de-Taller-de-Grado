<?php

namespace Tests\Feature;

use App\Models\AsignacionRol;
use App\Models\EspacioAcademico;
use App\Models\Permiso;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReservationConcurrencyTest extends TestCase
{
    private array $workers = [];

    private string $prefix;

    private string $run;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh --seed --force')->assertSuccessful();
        $dir = base_path('.local/testing/concurrency');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }$this->run = bin2hex(random_bytes(8));
        $this->prefix = $dir.DIRECTORY_SEPARATOR.$this->run;
    }

    protected function tearDown(): void
    {
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }foreach ($this->workers as $p) {
                if ($p->isRunning()) {
                    $p->stop();
                }
            }foreach (glob($this->prefix.'.*') ?: [] as $file) {
                unlink($file);
            }$this->artisan('migrate:fresh --seed --force')->assertSuccessful();
        } finally {
            parent::tearDown();
        }
    }

    private function user(string $role): Usuario
    {
        $u = Usuario::factory()->create();
        (new AsignacionRol)->forceFill(['usuario_id' => $u->id, 'rol_id' => Rol::where('nombre', $role)->sole()->id, 'asignado_por_id' => $u->id, 'fecha_asignacion' => now()])->save();

        return $u;
    }

    private function waitFor(string $suffix): void
    {
        $deadline = microtime(true) + 12;
        while (! is_file($this->prefix.$suffix)) {
            if (microtime(true) > $deadline) {
                $this->fail('El proceso no alcanzó la barrera '.$suffix);
            }usleep(10000);
        }
    }

    private function start(array $input): Process
    {
        $slot = count($this->workers);
        $p = new Process([PHP_BINARY, base_path('tests/Support/concurrent-reservations.php')], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => '.local/testing/config.php'], json_encode([...$input, 'run' => $this->run, 'slot' => $slot], JSON_THROW_ON_ERROR), 25);
        $p->start();
        $this->workers[] = $p;

        return $p;
    }

    private function workerResult(Process $p): array
    {
        $p->wait();
        $this->assertTrue($p->isSuccessful(), 'Worker result: '.$p->getOutput());
        $r = json_decode($p->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $r['transactions']);

        return $r;
    }

    private function race(array $a, array $b): array
    {
        $p = $this->start($a);
        $q = $this->start($b);
        $this->waitFor('.0.ready');
        $this->waitFor('.1.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $ra = $this->workerResult($p);
        $rb = $this->workerResult($q);
        $this->assertNotSame($ra['connection'], $rb['connection']);
        $statuses = [$ra['status'], $rb['status']];
        sort($statuses);

        return $statuses;
    }

    public function test_two_processes_can_create_overlapping_pending_requests(): void
    {
        $u = $this->user('Docente');
        $s = EspacioAcademico::factory()->create();
        $input = ['action' => 'create', 'actor' => $u->id, 'data' => ['espacio_id' => $s->getKey(), 'inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00', 'proposito' => 'Solicitud concurrente sintética']];
        $this->assertSame(['ok', 'ok'], $this->race($input, $input));
        $this->assertSame(2, Reserva::where('estado', 'PENDIENTE')->count());
        $this->assertSame(2, Reserva::distinct()->count('codigo'));
    }

    public function test_two_overlapping_approvals_produce_exactly_one_approved_and_one_pending(): void
    {
        $u = $this->user('Administrador');
        $a = Reserva::factory()->create();
        $b = Reserva::factory()->create(['espacio_id' => $a->espacio_id]);
        $base = ['action' => 'approve', 'actor' => $u->id];
        $this->assertSame(['conflict', 'ok'], $this->race([...$base, 'target' => $a->id], [...$base, 'target' => $b->id]));
        $this->assertSame(1, Reserva::where('estado', 'APROBADA')->count());
        $pending = Reserva::where('estado', 'PENDIENTE')->sole();
        $this->assertNull($pending->resuelta_en);
        $this->assertNull($pending->resuelta_por_id);
        $this->assertNull($pending->motivo_rechazo);
    }

    public function test_adjacent_approvals_both_succeed_concurrently(): void
    {
        $u = $this->user('Administrador');
        $a = Reserva::factory()->create();
        $b = Reserva::factory()->create(['espacio_id' => $a->espacio_id, 'inicio' => '2090-01-10 09:00', 'fin' => '2090-01-10 10:00']);
        $base = ['action' => 'approve', 'actor' => $u->id];
        $this->assertSame(['ok', 'ok'], $this->race([...$base, 'target' => $a->id], [...$base, 'target' => $b->id]));
        $this->assertSame(2, Reserva::where('estado', 'APROBADA')->count());
    }

    public function test_duplicate_cancellations_write_once(): void
    {
        $u = $this->user('Docente');
        $r = Reserva::factory()->create(['solicitante_id' => $u->id]);
        $input = ['action' => 'cancel', 'actor' => $u->id, 'target' => $r->id];
        $this->assertSame(['conflict', 'ok'], $this->race($input, $input));
        $this->assertSame('CANCELADA', $r->fresh()->estado->value);
        $this->assertSame($u->id, $r->fresh()->cancelada_por_id);
    }

    public function test_approval_racing_owner_cancellation_never_resurrects_cancelled_reservation(): void
    {
        $u = $this->user('Docente');
        $admin = $this->user('Administrador');
        $r = Reserva::factory()->create(['solicitante_id' => $u->id]);
        $results = $this->race(['action' => 'approve', 'actor' => $admin->id, 'target' => $r->id], ['action' => 'cancel', 'actor' => $u->id, 'target' => $r->id]);
        $this->assertContains($results, [['ok', 'ok'], ['conflict', 'ok']]);
        $r->refresh();
        $this->assertSame('CANCELADA', $r->estado->value);
        $this->assertSame($u->id, $r->cancelada_por_id);
        $this->assertSame($r->resuelta_en === null, $r->resuelta_por_id === null);
    }

    public function test_approval_racing_rejection_has_one_resolution(): void
    {
        $u = $this->user('Administrador');
        $r = Reserva::factory()->create();
        $base = ['actor' => $u->id, 'target' => $r->id];
        $this->assertSame(['conflict', 'ok'], $this->race([...$base, 'action' => 'approve'], [...$base, 'action' => 'reject']));
        $this->assertContains($r->fresh()->estado->value, ['APROBADA', 'RECHAZADA']);
        $this->assertNotNull($r->fresh()->resuelta_en);
    }

    public function test_space_lock_wait_sees_approval_committed_after_worker_snapshot(): void
    {
        $u = $this->user('Administrador');
        $r = Reserva::factory()->create();
        DB::beginTransaction();
        EspacioAcademico::whereKey($r->espacio_id)->lockForUpdate()->firstOrFail();
        $p = $this->start(['action' => 'approve', 'actor' => $u->id, 'target' => $r->id]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.locking');
        usleep(200000);
        $this->assertTrue($p->isRunning());
        $this->assertSame('', $p->getOutput());
        // Worker already performed its ordinary actor read inside its transaction, establishing an RR snapshot.
        Reserva::factory()->create(['espacio_id' => $r->espacio_id, 'estado' => 'APROBADA']);
        DB::commit();
        $this->assertSame('conflict', $this->workerResult($p)['status']);
        $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
        $this->assertSame(1, Reserva::where('estado', 'APROBADA')->count());
    }

    public function test_cancellation_uses_clock_after_space_lock_wait(): void
    {
        $u = $this->user('Docente');
        $r = Reserva::factory()->create(['solicitante_id' => $u->id]);
        DB::beginTransaction();
        EspacioAcademico::whereKey($r->espacio_id)->lockForUpdate()->firstOrFail();
        $p = $this->start(['action' => 'cancel', 'actor' => $u->id, 'target' => $r->id, 'clock' => '2090-01-10 07:59:59', 'clock_after_lock' => '2090-01-10 08:00:00']);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.locking');
        $this->assertTrue($p->isRunning());
        DB::commit();
        $this->assertSame('conflict', $this->workerResult($p)['status']);
        $this->assertNull($r->fresh()->cancelada_en);
    }

    public function test_revoked_permission_is_rechecked_after_security_lock_wait(): void
    {
        $u = $this->user('Administrador');
        $r = Reserva::factory()->create();
        DB::beginTransaction();
        $role = Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $p = $this->start(['action' => 'approve', 'actor' => $u->id, 'target' => $r->id]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.security');
        $role->permisos()->detach(Permiso::where('codigo', 'reservas.resolver')->sole());
        DB::commit();
        $this->assertSame('denied', $this->workerResult($p)['status']);
        $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
    }
}
