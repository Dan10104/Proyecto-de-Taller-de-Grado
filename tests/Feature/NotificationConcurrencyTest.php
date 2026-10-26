<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Permiso;
use App\Models\Prestamo;
use App\Models\RegistroActividad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\LoanService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\NotificationConcurrencyTestCase;

#[Group('notifications')]
class NotificationConcurrencyTest extends NotificationConcurrencyTestCase
{
    public static function contenders(): array
    {
        return [['generate'], ['reconcile']];
    }

    #[DataProvider('contenders')]
    public function test_generator_and_reconciler_wait_for_committed_batch_without_duplicates(string $second): void
    {
        $a = $this->activity('PRESTAMO_VENCIDO');
        $p = $this->start(['action' => 'generate', 'id' => $a->id, 'hold' => 'insert']);
        $this->go();
        $this->waitFor('.0.held');
        $q = $this->start(['action' => $second, 'id' => $a->id]);
        $this->waitFor('.1.locking');
        $this->release(0);
        $x = $this->workerResult($p);
        $y = $this->workerResult($q);
        $this->assertNotSame($x['connection'], $y['connection']);
        $this->assertSame(2, $x['result']);
        $this->assertSame(0, $y['result']);
        $this->assertDatabaseCount('notificaciones', 2);
        $this->assertSame(2, DB::table('notificaciones')->select('registro_actividad_id', 'destinatario_id', 'tipo')->distinct()->count());
    }

    public function test_distinct_activities_with_shared_recipients_progress_while_first_holds_inserts_and_business_locks(): void
    {
        $s = $this->user('Docente');
        $admin = $this->user('Administrador');
        $a = $this->activity('PRESTAMO_VENCIDO', $s, $admin);
        $b = $this->activity('PRESTAMO_VENCIDO', $s, $admin);
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        DB::table('prestamos')->where('id', $b->prestamo_id)->lockForUpdate()->first();
        $p = $this->start(['action' => 'generate', 'id' => $a->id, 'hold' => 'insert']);
        $this->go();
        $this->waitFor('.0.held');
        $q = $this->start(['action' => 'generate', 'id' => $b->id]);
        $this->assertSame(2, $this->workerResult($q)['result']);
        $this->assertTrue($p->isRunning());
        $this->release(0);
        $this->assertSame(2, $this->workerResult($p)['result']);
        DB::rollBack();
        $this->assertDatabaseCount('notificaciones', 4);
    }

    public function test_killed_worker_mid_batch_rolls_back_and_recovery_materializes_whole_batch(): void
    {
        $a = $this->activity('RESERVA_SOLICITADA');
        $role = Rol::where('nombre', 'Administrador')->sole();
        $prototype = $this->user('Administrador')->getAttributes();
        for ($i = 0; $i < 100; $i++) {
            unset($prototype['id']);
            $prototype['correo'] = 'batch-'.$i.'@example.test';
            $id = DB::table('usuarios')->insertGetId($prototype);
            DB::table('asignaciones_roles')->insert(['usuario_id' => $id, 'rol_id' => $role->id, 'asignado_por_id' => $a->actor_id, 'fecha_asignacion' => now()]);
        }
        $p = $this->start(['action' => 'generate', 'id' => $a->id, 'hold' => 'insert']);
        $this->go();
        $this->waitFor('.0.held');
        $this->assertDatabaseCount('notificaciones', 0);
        $p->stop(0);
        $this->assertSame(102, $this->generate($a));
        $this->assertDatabaseCount('notificaciones', 102);
        $this->assertSame(0, $this->generate($a));
    }

    public static function cutChanges(): array
    {
        return [['permission', false], ['permission', true], ['inactive', false], ['inactive', true]];
    }

    #[DataProvider('cutChanges')]
    public function test_permission_or_deactivation_committed_before_or_after_snapshot_has_defined_effect(string $change, bool $after): void
    {
        $a = $this->activity('RESERVA_SOLICITADA');
        $admin = Usuario::where('id', '!=', $a->actor_id)->sole();
        if (! $after) {
            DB::beginTransaction();
            RegistroActividad::whereKey($a->id)->lockForUpdate()->first();
        }
        $p = $this->start(['action' => 'generate', 'id' => $a->id, ...($after ? ['hold' => 'snapshot'] : [])]);
        $this->go();
        $this->waitFor($after ? '.0.held' : '.0.locking');
        if ($change === 'inactive') {
            DB::table('usuarios')->where('id', $admin->id)->update(['estado' => 'INACTIVO']);
        } else {
            DB::table('roles_permisos')->where('permiso_id', Permiso::where('codigo', 'reservas.resolver')->sole()->id)->delete();
        }
        if ($after) {
            $this->release(0);
        } else {
            DB::commit();
        }
        $result = $this->workerResult($p);
        $this->assertSame('ok', $result['status']);
        $this->assertSame($after ? 1 : 0, $result['result']);
    }

    public function test_two_readers_keep_the_first_conditional_write_timestamp(): void
    {
        $u = $this->user('Docente');
        $n = Notificacion::factory()->create(['destinatario_id' => $u->id]);
        // Hold the record in a separate transaction so both readers reach their conditional UPDATE.
        DB::beginTransaction();
        DB::table('notificaciones')->where('id', $n->id)->lockForUpdate()->first();
        $p = $this->start(['action' => 'read', 'id' => $n->id, 'actor' => $u->id, 'time' => '2090-01-10 08:15:00']);
        $q = $this->start(['action' => 'read', 'id' => $n->id, 'actor' => $u->id, 'time' => '2090-01-10 08:15:01']);
        $this->go();
        $this->waitFor('.0.reading');
        $this->waitFor('.1.reading');
        DB::commit();
        $x = $this->workerResult($p);
        $y = $this->workerResult($q);
        $this->assertSame('ok', $x['status']);
        $this->assertSame('ok', $y['status']);
        $this->assertSame($x['result'], $y['result']);
        $this->assertContains($n->fresh()->leida_en->toDateTimeString(), ['2090-01-10 08:15:00', '2090-01-10 08:15:01']);
    }

    private function delivered(): Prestamo
    {
        $a = $this->user('Administrador');
        $p = Prestamo::factory()->create();
        app(LoanService::class)->approve($a, $p);
        app(LoanService::class)->deliver($a, $p, ['condicion_entrega' => 'Completo', 'equipo_verificado' => true, 'receptor_verificado' => true]);
        $this->travel(1)->hours();

        return $p;
    }

    public function test_two_overdue_detectors_record_one_activity_then_generate_one_batch(): void
    {
        $loan = $this->delivered();
        $p = $this->start(['action' => 'overdue', 'id' => $loan->id, 'hold' => 'event']);
        $this->go();
        $this->waitFor('.0.held');
        $q = $this->start(['action' => 'overdue', 'id' => $loan->id]);
        $this->waitFor('.1.loan');
        $this->release(0);
        $this->assertTrue($this->workerResult($p)['result']);
        $this->assertFalse($this->workerResult($q)['result']);
        $a = RegistroActividad::where('accion', 'PRESTAMO_VENCIDO')->sole();
        $this->assertSame(2, $this->generate($a));
        $this->assertSame(0, $this->generate($a));
        $this->assertSame('ENTREGADO', $loan->fresh()->estado->value);
    }

    public static function orders(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('orders')]
    public function test_return_and_overdue_serialize_in_both_orders(bool $returnFirst): void
    {
        $loan = $this->delivered();
        $actor = $this->user('Administrador');
        $first = ['action' => $returnFirst ? 'return' : 'overdue', 'id' => $loan->id, 'actor' => $actor->id, 'hold' => $returnFirst ? 'return' : 'event'];
        $second = ['action' => $returnFirst ? 'overdue' : 'return', 'id' => $loan->id, 'actor' => $actor->id];
        $p = $this->start($first);
        $this->go();
        $this->waitFor('.0.held');
        $q = $this->start($second);
        $this->release(0);
        $x = $this->workerResult($p);
        $y = $this->workerResult($q);
        $this->assertSame('ok', $x['status']);
        $this->assertSame('ok', $y['status']);
        $this->assertSame('DEVUELTO', $loan->fresh()->estado->value);
        $this->assertSame($returnFirst ? 0 : 1, RegistroActividad::where('accion', 'PRESTAMO_VENCIDO')->count());
        foreach (RegistroActividad::where('accion', 'PRESTAMO_VENCIDO')->get() as $a) {
            $this->assertGreaterThan(0, $this->generate($a));
        }
    }

    public function test_cu23_contenders_create_one_noncritical_event_and_notification_batch(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $this->user('Administrador');
        $p = $this->start(['action' => 'release', 'id' => $r->id, 'hold' => 'event']);
        $this->go();
        $this->waitFor('.0.held');
        $q = $this->start(['action' => 'release', 'id' => $r->id]);
        $this->assertSame('deferred', $this->workerResult($q)['status']);
        $this->release(0);
        $this->assertSame('LIBERAR', $this->workerResult($p)['result']);
        $this->assertNull($this->evaluate($r));
        $this->assertDatabaseCount('evaluaciones_liberacion', 1);
        $a = RegistroActividad::sole();
        $this->assertFalse($a->critica);
        $this->assertSame(2, $this->generate($a));
        $this->assertSame(0, $this->generate($a));
    }

    public function test_lock_timeout_retries_three_times_without_partial_generation_or_business_rollback(): void
    {
        $a = $this->activity();
        DB::beginTransaction();
        RegistroActividad::whereKey($a->id)->lockForUpdate()->first();
        $p = $this->start(['action' => 'generate', 'id' => $a->id]);
        $this->go();
        $r = $this->workerResult($p);
        $this->assertSame('database_1205', $r['status']);
        $this->assertSame(3, $r['attempts']);
        DB::rollBack();
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertTrue(RegistroActividad::whereKey($a->id)->exists());
        $this->assertSame(1, $this->generate($a));
    }
}
