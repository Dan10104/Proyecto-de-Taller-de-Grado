<?php

namespace Tests\Feature;

use App\Models\AsignacionRol;
use App\Models\CodigoQr;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Permiso;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\SesionAcceso;
use App\Models\Usuario;
use App\Models\ValidacionQr;
use App\Services\LoanService;
use App\Services\QrManagementService;
use App\Services\QrTokenService;
use App\Services\QrValidationService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class QrConcurrencyTest extends TestCase
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
        $p = new Process([PHP_BINARY, base_path('tests/Support/concurrent-qr.php')], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => '.local/testing/config.php'], json_encode([...$input, 'run' => $this->run, 'slot' => $slot], JSON_THROW_ON_ERROR), 25);
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

    private function code(array $goal, array $extra = []): array
    {
        $token = app(QrTokenService::class)->generate();
        $code = CodigoQr::factory()->create(array_merge(['recurso_id' => null, 'reserva_id' => null, 'prestamo_id' => null, 'token_hash' => hash('sha256', $token)], $goal, $extra));

        return [$code, $token];
    }

    private function space(): Reserva
    {
        return Reserva::factory()->aprobada()->create(['inicio' => now()->subHour(), 'fin' => now()->addHour()]);
    }

    public function test_two_rotations_keep_history_and_exactly_one_active_goal(): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        [$old] = $this->code(['recurso_id' => $r->espacio_id]);
        $input = ['action' => 'issue', 'actor' => $a->id, 'target' => $r->espacio_id, 'type' => 'recurso'];
        $this->assertSame(['ok', 'ok'], $this->race($input, $input));
        $this->assertDatabaseCount('codigos_qr', 3);
        $this->assertSame(1, CodigoQr::where('activo', true)->count());
        $this->assertFalse($old->fresh()->activo);
    }

    public static function operations(): array
    {
        return [['deliver', 'ADECUADA'], ['receive', 'ADECUADA'], ['receive', 'NO_DETERMINABLE']];
    }

    #[DataProvider('operations')]
    public function test_two_confirmations_preserve_one_hito_and_one_valid_link(string $action, string $condition): void
    {
        $a = $this->user('Administrador');
        $p = Prestamo::factory()->create();
        $service = app(LoanService::class);
        $service->approve($a, $p);
        if ($action === 'receive') {
            $service->deliver($a, $p, ['condicion_entrega' => 'Inspección', 'equipo_verificado' => true, 'receptor_verificado' => true]);
        }
        [$code,$token] = $this->code(['prestamo_id' => $p->id]);
        $input = ['action' => $action, 'actor' => $a->id, 'target' => $p->id, 'token' => $token, 'condition' => $condition];
        $this->assertSame(['INVALIDA', 'VALIDA'], $this->race($input, $input));
        $table = $action === 'deliver' ? 'entregas_prestamo' : 'devoluciones_prestamo';
        $field = $action === 'deliver' ? 'entrega_id' : 'devolucion_id';
        $this->assertDatabaseCount($table, 1);
        $this->assertDatabaseCount('validaciones_qr', 2);
        $this->assertSame(1, ValidacionQr::whereNotNull($field)->count());
        $this->assertNull(ValidacionQr::where('resultado', 'INVALIDA')->sole()->$field);
        $this->assertSame($action === 'deliver' || $condition === 'NO_DETERMINABLE' ? 'ENTREGADO' : 'DEVUELTO', $p->fresh()->estado->value);
    }

    public static function qrChanges(): array
    {
        return [['deactivate'], ['expire']];
    }

    #[DataProvider('qrChanges')]
    public function test_qr_lock_current_read_rejects_commit_after_prior_snapshot(string $change): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        [$code,$token] = $this->code(['reserva_id' => $r->id], ['generada_en' => now()->subHour()]);
        DB::beginTransaction();
        CodigoQr::whereKey($code->id)->lockForUpdate()->firstOrFail();
        $worker = $this->start(['action' => 'space', 'actor' => $a->id, 'target' => $r->id, 'token' => $token]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.qr');
        $this->assertTrue($worker->isRunning());
        if ($change === 'expire') {
            $code->forceFill(['vence_en' => now()])->save();
        } else {
            $code->forceFill(['activo' => false])->save();
            if ($change === 'rotate') {
                $this->code(['reserva_id' => $r->id]);
            }
        }
        DB::commit();
        $this->assertSame('INVALIDA', $this->workerResult($worker)['status']);
        $v = ValidacionQr::sole();
        $this->assertSame($code->id, $v->codigo_qr_id);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
    }

    public function test_rotation_commits_before_validation_and_rejects_old_code(): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        [$code,$token] = $this->code(['reserva_id' => $r->id]);
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $worker = $this->start(['action' => 'space', 'actor' => $a->id, 'target' => $r->id, 'token' => $token]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.security');
        app(QrManagementService::class)->issue($a, 'reserva', $r->id);
        DB::commit();
        $this->assertSame('INVALIDA', $this->workerResult($worker)['status']);
        $this->assertSame($code->id, ValidacionQr::sole()->codigo_qr_id);
        $this->assertSame(1, CodigoQr::where('activo', true)->count());
    }

    public function test_expiry_clock_is_sampled_after_lock_wait(): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        $expiry = now()->startOfSecond()->addSeconds(2);
        [$code,$token] = $this->code(['reserva_id' => $r->id], ['generada_en' => now()->subHour(), 'vence_en' => $expiry]);
        DB::beginTransaction();
        CodigoQr::whereKey($code->id)->lockForUpdate()->firstOrFail();
        $worker = $this->start(['action' => 'space', 'actor' => $a->id, 'target' => $r->id, 'token' => $token]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.qr');
        while (now()->lt($expiry)) {
            usleep(10000);
        }
        DB::commit();
        $this->assertSame('INVALIDA', $this->workerResult($worker)['status']);
        $this->assertTrue(ValidacionQr::sole()->fecha_hora->greaterThanOrEqualTo($expiry));
    }

    public static function reverseChanges(): array
    {
        return [['deactivate'], ['issue']];
    }

    #[DataProvider('reverseChanges')]
    public function test_validation_committed_before_rotation_or_revocation_keeps_historical_evidence(string $action): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        [$code,$token] = $this->code(['reserva_id' => $r->id]);
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $worker = $this->start(['action' => $action, 'actor' => $a->id, 'target' => $r->id, 'type' => 'reserva', 'code' => $code->id]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.security');
        $v = app(QrValidationService::class)->useSpace($a, $r, $token);
        $this->assertSame('VALIDA', $v->resultado->value);
        DB::commit();
        $this->assertSame('ok', $this->workerResult($worker)['status']);
        $this->assertFalse($code->fresh()->activo);
        $this->assertSame('VALIDA', $v->fresh()->resultado->value);
        $this->assertSame($code->id, $v->fresh()->codigo_qr_id);
    }

    public function test_permission_withdrawn_during_rbac_wait_denies_without_attempt(): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        [$code,$token] = $this->code(['reserva_id' => $r->id]);
        DB::beginTransaction();
        $role = Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $worker = $this->start(['action' => 'space', 'actor' => $a->id, 'target' => $r->id, 'token' => $token]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.security');
        $role->permisos()->detach(Permiso::where('codigo', 'qr.validar_uso_espacio')->sole());
        DB::commit();
        $this->assertSame('denied', $this->workerResult($worker)['status']);
        $this->assertDatabaseCount('validaciones_qr', 0);
    }

    public function test_cancelled_reservation_after_resource_wait_is_read_current(): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        [$code,$token] = $this->code(['reserva_id' => $r->id]);
        DB::beginTransaction();
        EspacioAcademico::whereKey($r->espacio_id)->lockForUpdate()->sole();
        $worker = $this->start(['action' => 'space', 'actor' => $a->id, 'target' => $r->id, 'token' => $token]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.locking');
        $r->forceFill(['estado' => 'CANCELADA'])->save();
        DB::commit();
        $this->assertSame('INVALIDA', $this->workerResult($worker)['status']);
        $this->assertSame('CANCELADA', $r->fresh()->estado->value);
    }

    public static function businessChanges(): array
    {
        return [['state'], ['incident']];
    }

    #[DataProvider('businessChanges')]
    public function test_delivery_after_resource_wait_sees_changed_loan_or_new_incident(string $change): void
    {
        $a = $this->user('Administrador');
        $p = Prestamo::factory()->create();
        app(LoanService::class)->approve($a, $p);
        [$code,$token] = $this->code(['prestamo_id' => $p->id]);
        DB::beginTransaction();
        EquipamientoTecnologico::whereKey($p->equipamiento_id)->lockForUpdate()->sole();
        $worker = $this->start(['action' => 'deliver', 'actor' => $a->id, 'target' => $p->id, 'token' => $token]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor('.0.locking');
        if ($change === 'state') {
            $p->forceFill(['estado' => 'RECHAZADO'])->save();
        } else {
            IncidenciaMantenimiento::factory()->create(['recurso_id' => $p->equipamiento_id]);
        }
        DB::commit();
        $this->assertSame('INVALIDA', $this->workerResult($worker)['status']);
        $this->assertDatabaseCount('entregas_prestamo', 0);
        $this->assertNull(ValidacionQr::sole()->entrega_id);
    }

    public static function identityChanges(): array
    {
        return [['account'], ['session']];
    }

    #[DataProvider('identityChanges')]
    public function test_web_identity_is_current_after_wait_and_held_until_commit(string $change): void
    {
        $a = $this->user('Administrador');
        $r = $this->space();
        [$code,$token] = $this->code(['reserva_id' => $r->id]);
        $hash = hash('sha256', random_bytes(32));
        $session = (new SesionAcceso)->forceFill(['usuario_id' => $a->id, 'token_hash' => $hash, 'creada_en' => now(), 'expira_en' => now()->addHour()]);
        $session->save();
        DB::beginTransaction();
        if ($change === 'account') {
            Usuario::whereKey($a->id)->lockForUpdate()->sole();
        } else {
            SesionAcceso::whereKey($session->id)->lockForUpdate()->sole();
        }
        $worker = $this->start(['action' => 'space', 'actor' => $a->id, 'target' => $r->id, 'token' => $token, 'session_hash' => $hash]);
        $this->waitFor('.0.ready');
        file_put_contents($this->prefix.'.go', 'go');
        $this->waitFor($change === 'account' ? '.0.user' : '.0.session');
        if ($change === 'account') {
            $a->forceFill(['estado' => 'INACTIVO'])->save();
        } else {
            $session->forceFill(['revocada_en' => now()])->save();
        }
        DB::commit();
        $this->assertSame('denied', $this->workerResult($worker)['status']);
        $this->assertDatabaseCount('validaciones_qr', 0);
    }
}
