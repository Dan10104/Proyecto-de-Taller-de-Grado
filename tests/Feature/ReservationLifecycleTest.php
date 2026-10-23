<?php

namespace Tests\Feature;

use App\Enums\EstadoReserva;
use App\Exceptions\ReservationConflictException;
use App\Models\EspacioAcademico;
use App\Models\Permiso;
use App\Models\Reserva;
use App\Models\Rol;
use App\Services\SpaceAvailabilityService;
use App\Services\SpaceManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use Tests\ReservationTestCase;

class ReservationLifecycleTest extends ReservationTestCase
{
    private function available(Reserva $r): bool
    {
        return app(SpaceAvailabilityService::class)->candidates([], $r->inicio, $r->fin)->whereKey($r->espacio_id)->exists();
    }

    public function test_approval_conflict_rejection_and_cancel_preserve_history_and_restore_availability(): void
    {
        $owner = $this->user('Docente');
        $admin = $this->user('Administrador');
        $s = EspacioAcademico::factory()->create();
        $a = $this->service()->create($owner, $this->requestData($s));
        $b = $this->service()->create($owner, $this->requestData($s));
        $this->assertTrue($this->available($a));
        $this->login($admin);
        $this->post('/reservas/'.$a->id.'/aprobar')->assertSessionHasNoErrors()->assertRedirect();
        $a->refresh();
        $this->assertSame(EstadoReserva::APROBADA, $a->estado);
        $this->assertSame($admin->id, $a->resuelta_por_id);
        $this->assertNotNull($a->resuelta_en);
        $this->assertNull($a->motivo_rechazo);
        $this->assertFalse($this->available($a));
        $this->withCredentials()->postJson('/reservas/'.$b->id.'/aprobar')->assertStatus(409)->assertJsonPath('message', 'El espacio ya no se encuentra disponible para el periodo solicitado.');
        $this->assertSame(EstadoReserva::PENDIENTE, $b->fresh()->estado);
        $this->assertNull($b->fresh()->resuelta_en);
        $this->post('/reservas/'.$b->id.'/rechazar', ['motivo_rechazo' => '  Otro horario  '])->assertSessionHasNoErrors();
        $b->refresh();
        $this->assertSame('Otro horario', $b->motivo_rechazo);
        $this->assertSame($admin->id, $b->resuelta_por_id);
        $resolved = $a->resuelta_en->format('Y-m-d H:i:s');
        $this->post('/reservas/'.$a->id.'/cancelar')->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertSame(EstadoReserva::CANCELADA, $a->estado);
        $this->assertSame($resolved, $a->resuelta_en->format('Y-m-d H:i:s'));
        $this->assertSame($admin->id, $a->cancelada_por_id);
        $this->assertNotNull($a->cancelada_en);
        $this->assertNull($a->motivo_cancelacion);
        $this->assertTrue($this->available($a));
    }

    public static function changes(): array
    {
        return [['BLOQUEADO'], ['EN_MANTENIMIENTO'], ['bloqueo'], ['aprobada']];
    }

    #[DataProvider('changes')]
    public function test_request_and_approval_revalidate_current_conditions_without_partial_writes(string $change): void
    {
        $r = $this->pending();
        $s = $r->espacio;
        $admin = $this->user('Administrador');
        if (in_array($change, ['BLOQUEADO', 'EN_MANTENIMIENTO'])) {
            app(SpaceManagementService::class)->state($admin, $s, $change);
        } elseif ($change === 'bloqueo') {
            app(SpaceManagementService::class)->block($admin, $s, ['inicio' => $r->inicio->format('Y-m-d\TH:i'), 'fin' => $r->fin->format('Y-m-d\TH:i'), 'motivo' => 'Cambio operativo de CU05']);
        } else {
            $this->pending($r->solicitante, $s, ['estado' => 'APROBADA']);
        }
        $count = Reserva::count();
        $level = DB::transactionLevel();
        foreach ([fn () => $this->service()->create($r->solicitante, $this->requestData($s)), fn () => $this->service()->approve($admin, $r)] as $operation) {
            try {
                $operation();
                $this->fail('Debe rechazar la operación');
            } catch (ReservationConflictException) {
                $this->assertSame($level, DB::transactionLevel());
            }
        }
        $this->assertSame($count, Reserva::count());
        $this->assertSame(EstadoReserva::PENDIENTE, $r->fresh()->estado);
        $this->assertNull($r->fresh()->resuelta_en);
    }

    public static function terminalStates(): array
    {
        return [['APROBADA'], ['RECHAZADA'], ['CANCELADA'], ['LIBERADA'], ['FINALIZADA']];
    }

    #[DataProvider('terminalStates')]
    public function test_only_pending_can_be_resolved(string $state): void
    {
        $r = $this->pending(extra: ['estado' => $state]);
        $admin = $this->user('Administrador');
        $this->login($admin);
        foreach (['aprobar', 'rechazar'] as $action) {
            $this->withCredentials()->postJson('/reservas/'.$r->id.'/'.$action)->assertStatus(409);
            $this->assertSame($state, $r->fresh()->estado->value);
        }
    }

    public static function cancelCases(): array
    {
        return [['Docente', 'PENDIENTE'], ['Estudiante', 'APROBADA'], ['Administrador', 'PENDIENTE'], ['Encargado de Laboratorio', 'APROBADA']];
    }

    #[DataProvider('cancelCases')]
    public function test_cancellation_by_owner_or_authorized_manager(string $role, string $state): void
    {
        $u = $this->user($role);
        $owner = in_array($role, ['Docente', 'Estudiante']) ? $u : $this->user('Docente');
        $r = $this->pending($owner, extra: ['estado' => $state]);
        $this->login($u);
        $this->post('/reservas/'.$r->id.'/cancelar', ['motivo_cancelacion' => ' Cambio de agenda '])->assertSessionHasNoErrors();
        $r->refresh();
        $this->assertSame('CANCELADA', $r->estado->value);
        $this->assertSame($u->id, $r->cancelada_por_id);
        $this->assertSame('Cambio de agenda', $r->motivo_cancelacion);
        $this->assertNotNull($r->cancelada_en);
    }

    public function test_terminal_states_and_server_time_forbid_cancellation_even_for_administrator(): void
    {
        $admin = $this->user('Administrador');
        $this->login($admin);
        foreach (['RECHAZADA', 'CANCELADA', 'LIBERADA', 'FINALIZADA'] as $state) {
            $r = $this->pending(extra: ['estado' => $state]);
            $this->withCredentials()->postJson('/reservas/'.$r->id.'/cancelar')->assertStatus(409);
            $this->assertSame($state, $r->fresh()->estado->value);
        }
        $r = $this->pending();
        foreach ([$r->inicio, $r->inicio->addSecond()] as $time) {
            $this->travelTo($time);
            try {
                $this->service()->cancel($admin, $r);
                $this->fail('La hora del servidor impide cancelar');
            } catch (ReservationConflictException) {
                $this->assertNull($r->fresh()->cancelada_en);
            } finally {
                $this->travelBack();
            }
        }
    }

    public function test_optional_reasons_limits_and_protected_transition_fields(): void
    {
        $u = $this->user('Encargado de Laboratorio');
        $this->login($u);
        foreach (['rechazar' => 'motivo_rechazo', 'cancelar' => 'motivo_cancelacion'] as $action => $field) {
            $r = $this->pending();
            $this->post('/reservas/'.$r->id.'/'.$action, [$field => str_repeat('a', 501)])->assertSessionHasErrors($field);
            $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
            $this->post('/reservas/'.$r->id.'/'.$action, [$field => null])->assertSessionHasNoErrors();
            $this->assertNull($r->fresh()->$field);
        }
        $r = $this->pending();
        foreach (['codigo' => 'BAD', 'estado' => 'LIBERADA', 'solicitante_id' => 123, 'resuelta_por_id' => 123, 'resuelta_en' => '2090-01-01', 'espacio_id' => 999, 'inicio' => '2090-01-01', 'motivo_rechazo' => 'fake'] as $field => $value) {
            $this->post('/reservas/'.$r->id.'/aprobar', [$field => $value])->assertSessionHasErrors($field);
        }
        $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
    }

    public function test_collision_retry_is_bounded_and_only_collision_is_retried(): void
    {
        $r = $this->pending();
        $uuid = substr($r->codigo, 4);
        $u = $r->solicitante;
        $data = $this->requestData($r->espacio);
        Str::createUuidsUsingSequence([Uuid::fromString($uuid), Uuid::uuid4()]);
        try {
            $next = $this->service()->create($u, $data);
            $this->assertNotSame($r->codigo, $next->codigo);
        } finally {
            Str::createUuidsNormally();
        }
        $attempts = 0;
        Str::createUuidsUsing(function () use (&$attempts, $uuid) {
            $attempts++;

            return Uuid::fromString($uuid);
        });
        try {
            $this->service()->create($u, $data);
            $this->fail('Debe agotar tres intentos');
        } catch (ReservationConflictException) {
            $this->assertSame(3, $attempts);
            $this->assertDatabaseCount('reservas', 2);
        } finally {
            Str::createUuidsNormally();
        }
    }

    public function test_failure_after_update_rolls_back_and_does_not_leave_transaction_open(): void
    {
        $r = $this->pending();
        $u = $this->user('Administrador');
        $level = DB::transactionLevel();
        $armed = true;
        DB::listen(function ($query) use (&$armed) {
            if ($armed && str_starts_with($query->sql, 'update `reservas`')) {
                $armed = false;
                throw new \RuntimeException('Fallo sintético después de escritura');
            }
        });
        try {
            $this->service()->approve($u, $r);
            $this->fail('Debe revertir');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo sintético después de escritura', $e->getMessage());
        }
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
        $this->assertNull($r->fresh()->resuelta_por_id);
        $this->assertNull($r->fresh()->resuelta_en);
    }

    public function test_approval_takes_space_lock_before_current_conflict_reads(): void
    {
        $r = $this->pending();
        $u = $this->user('Administrador');
        $sql = [];
        DB::listen(function ($q) use (&$sql) {
            $sql[] = strtolower($q->sql);
        });
        $this->service()->approve($u, $r);
        $positions = [];
        foreach ($sql as $i => $q) {
            if (str_contains($q, 'for update')) {
                if (str_contains($q, 'from `roles`')) {
                    $positions['role'] = $i;
                }if (str_contains($q, 'from `espacios_academicos`')) {
                    $positions['space'] ??= $i;
                }if (str_contains($q, 'from `reservas`') && str_contains($q, '`inicio`')) {
                    $positions['conflict'] = $i;
                }if (str_contains($q, 'from `bloqueos_espacio`')) {
                    $positions['block'] = $i;
                }
            }
        }
        $this->assertCount(4, $positions);
        $this->assertLessThan($positions['space'], $positions['role']);
        $this->assertLessThan($positions['block'], $positions['space']);
        $this->assertLessThan($positions['conflict'], $positions['space']);
    }

    public function test_failed_insert_rolls_back_and_is_not_retried_as_uuid_collision(): void
    {
        $u = $this->user('Docente');
        $space = EspacioAcademico::factory()->create();
        $attempts = 0;
        $level = DB::transactionLevel();
        DB::listen(function ($q) use (&$attempts) {
            if (str_starts_with($q->sql, 'insert into `reservas`')) {
                $attempts++;
                throw new \RuntimeException('Fallo sintético de persistencia');
            }
        });
        try {
            $this->service()->create($u, $this->requestData($space));
            $this->fail('Debe revertirse');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo sintético de persistencia', $e->getMessage());
        }
        $this->assertSame(1, $attempts);
        $this->assertSame($level, DB::transactionLevel());
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_cancel_does_not_clear_other_operational_restrictions(): void
    {
        $r = $this->pending(extra: ['estado' => 'APROBADA']);
        $u = $this->user('Administrador');
        $this->blockFor($r->espacio, $r->inicio->toDateTimeString(), $r->fin->toDateTimeString(), true, $u);
        $this->service()->cancel($u, $r);
        $this->assertFalse($this->available($r));
        $this->assertDatabaseHas('bloqueos_espacio', ['espacio_id' => $r->espacio_id, 'activo' => true]);
    }

    public function test_revoking_cancellation_permission_applies_to_the_next_request(): void
    {
        $u = $this->user('Docente');
        $r = $this->pending($u);
        $this->login($u);
        Rol::where('nombre', 'Docente')->sole()->permisos()->detach(Permiso::where('codigo', 'reservas.cancelar_propias')->sole());
        $this->post('/reservas/'.$r->id.'/cancelar')->assertForbidden();
        $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
    }
}
