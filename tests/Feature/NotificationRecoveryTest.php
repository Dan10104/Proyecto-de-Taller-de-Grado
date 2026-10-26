<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\RegistroActividad;
use App\Services\Notifications\NotificationReconciler;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\NotificationTestCase;

#[Group('notifications')]
class NotificationRecoveryTest extends NotificationTestCase
{
    public function test_reconcile_ten_times_preserves_snapshot_read_time_and_closed_administrative_group(): void
    {
        $a = $this->activity('PRESTAMO_VENCIDO');
        $this->assertSame(2, $this->generate($a));
        $n = Notificacion::first();
        DB::table('notificaciones')->where('id', $n->id)->update(['leida_en' => now()]);
        $before = DB::table('notificaciones')->orderBy('id')->get()->toJson();
        $late = $this->user('Administrador');
        $this->travel(1)->hours();
        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(0, app(NotificationReconciler::class)->run()['creados']);
        }
        $this->assertSame($before, DB::table('notificaciones')->orderBy('id')->get()->toJson());
        $this->assertDatabaseMissing('notificaciones', ['destinatario_id' => $late->id]);
    }

    public function test_partial_persisted_batch_repairs_only_active_direct_recipient(): void
    {
        $a = $this->activity('PRESTAMO_VENCIDO');
        $this->generate($a);
        DB::table('notificaciones')->where('destinatario_id', $a->usuario_afectado_id)->delete();
        $late = $this->user('Administrador');
        $this->assertSame(1, app(NotificationReconciler::class)->run()['creados']);
        $this->assertDatabaseCount('notificaciones', 2);
        $this->assertDatabaseMissing('notificaciones', ['destinatario_id' => $late->id]);
        $this->assertDatabaseHas('notificaciones', ['destinatario_id' => $a->usuario_afectado_id]);
        // A missing administrative row is never reconstructed from current permissions.
        DB::table('notificaciones')->where('destinatario_id', '!=', $a->usuario_afectado_id)->delete();
        $this->assertSame(0, $this->generate($a));
    }

    public function test_zero_recipients_no_marker_recovery_uses_current_permissions_then_closes(): void
    {
        $a = $this->activity('RESERVA_SOLICITADA');
        DB::table('usuarios')->where('id', '!=', $a->usuario_afectado_id)->update(['estado' => 'INACTIVO']);
        $this->assertSame(0, $this->generate($a));
        $this->assertDatabaseCount('notificaciones', 0);
        $new = $this->user('Administrador');
        $this->assertSame(1, $this->generate($a));
        $this->assertSame($new->id, Notificacion::sole()->destinatario_id);
        $this->user('Administrador');
        $this->assertSame(0, $this->generate($a));
    }

    public static function boundaries(): array
    {
        return [[0, 1], [86400, 1], [86401, 0], [-1, 0]];
    }

    #[DataProvider('boundaries')]
    public function test_exact_inclusive_twenty_four_hour_window(int $seconds, int $count): void
    {
        $a = $this->activity();
        $this->travel($seconds)->seconds();
        $this->assertSame($count, $this->generate($a));
        $this->assertDatabaseCount('notificaciones', $count);
    }

    public function test_activation_boundary_disabled_invalid_before_equal_and_restart(): void
    {
        $a = $this->activity();
        foreach (['', 'invalid', '2090-02-31 00:00:00', '2090-01-10 08:15:01'] as $date) {
            config(['notifications.events_from' => $date]);
            $this->assertSame(0, $this->generate($a));
        }
        config(['notifications.events_from' => '2090-01-10 08:15:00']);
        $this->assertSame(1, $this->generate($a));
        $this->travel(25)->hours();
        $this->assertSame(0, app(NotificationReconciler::class)->run()['explorados']);
        $this->assertSame('2090-01-10 08:15:00', config('notifications.events_from'));
    }

    public static function invalidEvents(): array
    {
        return [[['accion' => 'OTRA']], [['resultado' => 'ERROR']], [['resultado' => 'RECHAZO']], [['critica' => false]], [['reserva_id' => null]], [['usuario_afectado_id' => null]], [['proceso_responsable' => 'otro', 'actor_id' => null]]];
    }

    #[DataProvider('invalidEvents')]
    public function test_invalid_context_or_non_success_omitted(array $extra): void
    {
        $this->assertSame(0, $this->generate($this->activity(extra: $extra)));
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_inactive_direct_no_new_rows_history_survives_and_late_reactivation_no_backfill(): void
    {
        $a = $this->activity();
        $this->generate($a);
        $owner = $a->usuarioAfectado;
        $owner->forceFill(['estado' => 'INACTIVO'])->save();
        $second = $this->activity('RESERVA_RECHAZADA', $owner);
        $this->assertSame(0, $this->generate($second));
        $this->assertDatabaseCount('notificaciones', 1);
        $this->travel(25)->hours();
        $owner->forceFill(['estado' => 'ACTIVO'])->save();
        $this->assertSame(0, $this->generate($second));
    }

    public function test_snapshot_excludes_free_text_and_stays_historical_after_business_change(): void
    {
        $a = $this->activity();
        $r = $a->reserva;
        DB::table('reservas')->where('id', $r->id)->update(['proposito' => '<script>synthetic-private-reason</script>']);
        $this->travel(1)->hours();
        $this->generate($a);
        $n = Notificacion::sole();
        $this->assertStringNotContainsString('synthetic-private-reason', $n->mensaje);
        $this->assertStringContainsString('08:15:00', $n->mensaje);
        $this->assertSame('09:15:00', $n->creada_en->format('H:i:s'));
        DB::table('reservas')->where('id', $r->id)->update(['estado' => 'CANCELADA', 'cancelada_por_id' => $a->actor_id, 'cancelada_en' => now()]);
        $this->generate($a);
        $this->assertSame($n->mensaje, $n->fresh()->mensaje);
        $this->assertStringContainsString('fue aprobada', $n->mensaje);
    }

    public function test_failure_after_first_insert_rolls_back_entire_batch_and_reconciles(): void
    {
        $a = $this->activity('RESERVA_SOLICITADA');
        $armed = true;
        DB::listen(function ($q) use (&$armed) {
            if ($armed && str_starts_with($q->sql, 'insert into `notificaciones`')) {
                $armed = false;
                throw new \RuntimeException('synthetic failure');
            }
        });
        try {
            $this->generate($a);
            $this->fail('Expected rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('synthetic failure', $e->getMessage());
        }
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertTrue(RegistroActividad::whereKey($a->id)->exists());
        $this->assertSame(1, app(NotificationReconciler::class)->run()['creados']);
    }
}
