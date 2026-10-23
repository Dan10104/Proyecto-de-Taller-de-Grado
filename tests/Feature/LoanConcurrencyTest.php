<?php

namespace Tests\Feature;

use App\Models\AsignacionRol;
use App\Models\EquipamientoTecnologico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Permiso;
use App\Models\Prestamo;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\IncidentMaintenanceService;
use App\Services\LoanService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LoanConcurrencyTest extends TestCase
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
        $p = new Process([PHP_BINARY, base_path('tests/Support/concurrent-loans.php')], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => '.local/testing/config.php'], json_encode([...$input, 'run' => $this->run, 'slot' => $slot], JSON_THROW_ON_ERROR), 25);
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

    private function pending(): Prestamo
    {
        return Prestamo::factory()->create();
    }

    private function delivered(Usuario $actor): Prestamo
    {
        $p = $this->pending();
        app(LoanService::class)->approve($actor, $p);

        return app(LoanService::class)->deliver($actor, $p, ['condicion_entrega' => 'Inspección sintética', 'equipo_verificado' => true, 'receptor_verificado' => true]);
    }

    private function incident(Usuario $actor, int $resource): IncidenciaMantenimiento
    {
        return app(IncidentMaintenanceService::class)->open($actor, ['recurso_id' => $resource, 'tipo' => 'INCIDENCIA', 'descripcion' => 'Condición sintética', 'inicio' => '2026-01-01T08:00']);
    }

    public function test_two_incompatible_approvals_commit_one_and_leave_loser_pending_without_resolution(): void
    {
        $u = $this->user('Administrador');
        $p = $this->pending();
        $q = Prestamo::factory()->create(['equipamiento_id' => $p->equipamiento_id]);
        $base = ['action' => 'approve', 'actor' => $u->id];
        $this->assertSame(['conflict', 'ok'], $this->race([...$base, 'target' => $p->id], [...$base, 'target' => $q->id]));
        $this->assertSame(1, Prestamo::where('estado', 'APROBADO')->count());
        $loser = Prestamo::where('estado', 'PENDIENTE')->sole();
        $this->assertNull($loser->resuelto_por_id);
        $this->assertNull($loser->resuelto_en);
        $this->assertNull($loser->motivo_rechazo);
    }

    public function test_pending_requests_coexist_with_distinct_codes(): void
    {
        $u = $this->user('Docente');
        $e = EquipamientoTecnologico::factory()->create();
        $input = ['action' => 'create', 'actor' => $u->id, 'data' => ['equipamiento_id' => $e->getKey(), 'inicio_previsto' => '2090-01-10T08:00', 'fin_previsto' => '2090-01-10T09:00', 'motivo' => 'Solicitud concurrente']];
        $this->assertSame(['ok', 'ok'], $this->race($input, $input));
        $this->assertSame(2, Prestamo::where('estado', 'PENDIENTE')->count());
        $this->assertSame(2, Prestamo::distinct()->count('codigo'));
    }

    public function test_adjacent_approvals_both_succeed(): void
    {
        $u = $this->user('Administrador');
        $p = $this->pending();
        $q = Prestamo::factory()->create(['equipamiento_id' => $p->equipamiento_id, 'inicio_previsto' => '2090-01-10 09:00', 'fin_previsto' => '2090-01-10 10:00']);
        $base = ['action' => 'approve', 'actor' => $u->id];
        $this->assertSame(['ok', 'ok'], $this->race([...$base, 'target' => $p->id], [...$base, 'target' => $q->id]));
        $this->assertSame(2, Prestamo::where('estado', 'APROBADO')->count());
    }

    public function test_approval_racing_rejection_preserves_one_decision(): void
    {
        $u = $this->user('Administrador');
        $p = $this->pending();
        $base = ['actor' => $u->id, 'target' => $p->id];
        $this->assertSame(['conflict', 'ok'], $this->race([...$base, 'action' => 'approve'], [...$base, 'action' => 'reject']));
        $this->assertContains($p->fresh()->estado->value, ['APROBADO', 'RECHAZADO']);
        $this->assertSame($u->id, $p->fresh()->resuelto_por_id);
    }

    public function test_double_delivery_writes_single_hito_and_atomic_custody(): void
    {
        $u = $this->user('Administrador');
        $p = $this->pending();
        app(LoanService::class)->approve($u, $p);
        $input = ['action' => 'deliver', 'actor' => $u->id, 'target' => $p->id];
        $this->assertSame(['conflict', 'ok'], $this->race($input, $input));
        $this->assertDatabaseCount('entregas_prestamo', 1);
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->estado->value);
    }

    public function test_double_return_preserves_first_hito_and_atomic_return(): void
    {
        $u = $this->user('Administrador');
        $p = $this->delivered($u);
        $input = ['action' => 'receive', 'actor' => $u->id, 'target' => $p->id, 'data' => ['condicion' => 'ADECUADA']];
        $this->assertSame(['conflict', 'ok'], $this->race($input, $input));
        $this->assertDatabaseCount('devoluciones_prestamo', 1);
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
        $this->assertSame('DISPONIBLE', $p->equipamiento->estado->value);
        $this->assertSame($u->id, $p->devolucion->recibido_por_id);
    }

    public function test_double_review_updates_same_return_once(): void
    {
        $u = $this->user('Administrador');
        $p = $this->delivered($u);
        app(LoanService::class)->receive($u, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Inspección pendiente']);
        $before = $p->devolucion;
        $input = ['action' => 'review', 'actor' => $u->id, 'target' => $p->id, 'data' => ['condicion' => 'ADECUADA', 'apto' => true]];
        $this->assertSame(['conflict', 'ok'], $this->race($input, $input));
        $this->assertDatabaseCount('devoluciones_prestamo', 1);
        $this->assertSame($before->id, $p->devolucion->id);
        $this->assertTrue($before->registrada_en->equalTo($before->fresh()->registrada_en));
        $this->assertSame('DEVUELTO', $p->fresh()->estado->value);
    }

    public function test_double_incident_close_has_single_responsible_and_solution(): void
    {
        $u = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $i = $this->incident($u, $e->getKey());
        $base = ['action' => 'close', 'actor' => $u->id, 'target' => $i->id];
        $this->assertSame(['conflict', 'ok'], $this->race([...$base, 'data' => ['solucion' => 'Solución A', 'apto' => true]], [...$base, 'data' => ['solucion' => 'Solución B', 'apto' => true]]));
        $this->assertSame('CERRADA', $i->fresh()->estado->value);
        $this->assertContains($i->fresh()->solucion, ['Solución A', 'Solución B']);
        $this->assertSame('DISPONIBLE', $e->fresh()->estado->value);
    }

    public function test_open_racing_close_never_leaves_available_with_open_incident(): void
    {
        $u = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $i = $this->incident($u, $e->getKey());
        $open = ['action' => 'open', 'actor' => $u->id, 'data' => ['recurso_id' => $e->getKey(), 'tipo' => 'MANTENIMIENTO', 'descripcion' => 'Segunda restricción', 'inicio' => '2026-01-01T08:00']];
        $close = ['action' => 'close', 'actor' => $u->id, 'target' => $i->id, 'data' => ['solucion' => 'Primera resuelta', 'apto' => true]];
        $this->assertSame(['ok', 'ok'], $this->race($open, $close));
        $this->assertSame(1, IncidenciaMantenimiento::where('estado', 'ABIERTA')->count());
        $this->assertSame('EN_MANTENIMIENTO', $e->fresh()->estado->value);
    }

    public function test_approval_after_equipment_lock_wait_reads_new_commit_despite_prior_snapshot(): void
    {
        $u = $this->user('Administrador');
        $p = $this->pending();
        DB::beginTransaction();
        EquipamientoTecnologico::whereKey($p->equipamiento_id)->lockForUpdate()->firstOrFail();
        $worker = $this->start(['action' => 'approve', 'actor' => $u->id, 'target' => $p->id]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.locking');
        usleep(200000);
        $this->assertTrue($worker->isRunning());
        Prestamo::factory()->create(['equipamiento_id' => $p->equipamiento_id, 'estado' => 'APROBADO']);
        DB::commit();
        $this->assertSame('conflict', $this->workerResult($worker)['status']);
        $this->assertSame('PENDIENTE', $p->fresh()->estado->value);
        $this->assertNull($p->fresh()->resuelto_en);
    }

    public function test_delivery_after_wait_sees_new_incident_even_if_physical_label_stale(): void
    {
        $u = $this->user('Administrador');
        $p = $this->pending();
        app(LoanService::class)->approve($u, $p);
        DB::beginTransaction();
        EquipamientoTecnologico::whereKey($p->equipamiento_id)->lockForUpdate()->firstOrFail();
        $worker = $this->start(['action' => 'deliver', 'actor' => $u->id, 'target' => $p->id]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.locking');
        IncidenciaMantenimiento::factory()->create(['recurso_id' => $p->equipamiento_id]);
        DB::commit();
        $this->assertSame('conflict', $this->workerResult($worker)['status']);
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->assertSame('APROBADO', $p->fresh()->estado->value);
    }

    public function test_close_after_wait_reads_other_open_incident_and_retains_restriction(): void
    {
        $u = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $i = $this->incident($u, $e->getKey());
        DB::beginTransaction();
        EquipamientoTecnologico::whereKey($e->getKey())->lockForUpdate()->firstOrFail();
        $worker = $this->start(['action' => 'close', 'actor' => $u->id, 'target' => $i->id, 'data' => ['solucion' => 'Resuelto', 'apto' => true]]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.locking');
        IncidenciaMantenimiento::factory()->create(['recurso_id' => $e->getKey()]);
        DB::commit();
        $this->assertSame('ok', $this->workerResult($worker)['status']);
        $this->assertSame('CERRADA', $i->fresh()->estado->value);
        $this->assertSame('EN_MANTENIMIENTO', $e->fresh()->estado->value);
    }

    public function test_permission_withdrawal_during_security_lock_wait_denies_mutation(): void
    {
        $u = $this->user('Administrador');
        $p = $this->pending();
        DB::beginTransaction();
        $role = Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $worker = $this->start(['action' => 'approve', 'actor' => $u->id, 'target' => $p->id]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.security');
        $role->permisos()->detach(Permiso::where('codigo', 'prestamos.resolver')->sole());
        DB::commit();
        $this->assertSame('denied', $this->workerResult($worker)['status']);
        $this->assertSame('PENDIENTE', $p->fresh()->estado->value);
    }

    public function test_open_waiting_for_close_commit_restores_restriction_in_reverse_serial_order(): void
    {
        $u = $this->user('Administrador');
        $e = EquipamientoTecnologico::factory()->create();
        $i = $this->incident($u, $e->getKey());
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        EquipamientoTecnologico::whereKey($e->getKey())->lockForUpdate()->sole();
        $worker = $this->start(['action' => 'open', 'actor' => $u->id, 'data' => ['recurso_id' => $e->getKey(), 'tipo' => 'MANTENIMIENTO', 'descripcion' => 'Restricción posterior', 'inicio' => '2026-01-01T08:00']]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.security');
        app(IncidentMaintenanceService::class)->close($u, $i, ['solucion' => 'Cierre previo', 'apto' => true]);
        $this->assertSame('DISPONIBLE', $e->fresh()->estado->value);
        DB::commit();
        $this->assertSame('ok',$this->workerResult($worker)['status']);
        $this->assertSame('EN_MANTENIMIENTO',$e->fresh()->estado->value);
        $this->assertSame(1,IncidenciaMantenimiento::where('estado','ABIERTA')->count());
    }
}
