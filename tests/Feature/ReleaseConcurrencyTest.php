<?php

namespace Tests\Feature;

use App\Models\CodigoQr;
use App\Models\EspacioAcademico;
use App\Models\EvaluacionLiberacion;
use App\Models\EventoOcupacion;
use App\Models\Permiso;
use App\Models\PoliticaLiberacion;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\ValidacionQr;
use App\Services\QrTokenService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReleaseConcurrencyTestCase;

class ReleaseConcurrencyTest extends ReleaseConcurrencyTestCase
{
    public static function rounds(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('rounds')]
    public function test_two_workers_commit_only_one_evaluation_for_first_or_same_second_novelty(bool $reevaluate): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA', 'usa_iot' => $reevaluate, 'max_antiguedad_lectura_min' => $reevaluate ? 5 : null]);
        if ($reevaluate) {
            $this->evaluate($r);
            $this->qr($r, ['resultado' => 'INVALIDA']);
        }
        $a = $this->start(['action' => 'evaluate', 'reservation' => $r->id]);
        $b = $this->start(['action' => 'evaluate', 'reservation' => $r->id]);
        $this->go();
        $ra = $this->workerResult($a);
        $rb = $this->workerResult($b);
        $this->assertNotSame($ra['connection'], $rb['connection']);
        $this->assertEqualsCanonicalizing([$reevaluate ? 'NO_DETERMINABLE' : 'LIBERAR', 'omitted'], [$ra['status'], $rb['status']]);
        $this->assertDatabaseCount('evaluaciones_liberacion', $reevaluate ? 2 : 1);
        $this->assertSame($reevaluate ? 'APROBADA' : 'LIBERADA', $r->fresh()->estado->value);
        $this->assertNull($this->evaluate($r));
    }

    public static function locks(): array
    {
        return [['reservas', 'id'], ['espacios_academicos', 'recurso_id'], ['politicas_liberacion', 'id'], ['sensores_iot', 'id']];
    }

    #[DataProvider('locks')]
    public function test_nowait_retries_exactly_three_times_without_partial_history_or_rbac(string $table, string $key): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $s = $this->sensor($r);
        $id = match ($table) {
            'reservas' => $r->id, 'espacios_academicos' => $r->espacio_id, 'politicas_liberacion' => $r->espacio->politica_liberacion_id, 'sensores_iot' => $s->id
        };
        DB::beginTransaction();
        DB::table($table)->where($key, $id)->lockForUpdate()->first();
        $p = $this->start(['action' => 'evaluate', 'reservation' => $r->id]);
        $this->go();
        $result = $this->workerResult($p);
        $this->assertSame('deferred', $result['status']);
        $this->assertSame(3, $result['attempts']);
        DB::rollBack();
        $this->assertDatabaseCount('evaluaciones_liberacion', 0);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        DB::beginTransaction();
        Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $p = $this->start(['action' => 'evaluate', 'reservation' => $r->id]);
        $this->assertSame('LIBERAR', $this->workerResult($p)['status']);
        DB::rollBack();
        $this->assertDatabaseCount('evaluaciones_liberacion', 1);
    }

    private function qrInput(Reserva $r): array
    {
        $token = app(QrTokenService::class)->generate();
        CodigoQr::factory()->create(['recurso_id' => $r->espacio_id, 'reserva_id' => null, 'prestamo_id' => null, 'token_hash' => hash('sha256', $token), 'generada_en' => now()->subMinute(), 'vence_en' => null]);

        return ['action' => 'qr', 'reservation' => $r->id, 'actor' => $r->solicitante_id, 'token' => $token];
    }

    public function test_qr_holding_space_makes_cu23_back_off_then_committed_validation_vetoes(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA'], ['solicitante_id' => $this->user('Docente')->id]);
        $a = $this->start([...$this->qrInput($r), 'hold' => 'space']);
        $this->go();
        $this->waitFor('.0.held');
        $b = $this->start(['action' => 'evaluate', 'reservation' => $r->id]);
        $this->assertSame('deferred', $this->workerResult($b)['status']);
        $this->release(0);
        $this->assertSame('VALIDA', $this->workerResult($a)['status']);
        $e = $this->evaluate($r);
        $this->assertSame('MANTENER_POR_USO', $e->resultado->value);
        $this->assertTrue($e->validaciones()->sole()->is(ValidacionQr::sole()));
    }

    public function test_cu23_commits_before_qr_and_waiting_qr_cannot_validate_released_reservation(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA'], ['solicitante_id' => $this->user('Docente')->id]);
        $input = $this->qrInput($r);
        $a = $this->start(['action' => 'evaluate', 'reservation' => $r->id, 'hold' => 'space']);
        $this->go();
        $this->waitFor('.0.held');
        $b = $this->start($input);
        $this->waitFor('.1.space');
        $this->release(0);
        $this->assertSame('LIBERAR', $this->workerResult($a)['status']);
        $this->assertSame('INVALIDA', $this->workerResult($b)['status']);
        $this->assertSame(0, EvaluacionLiberacion::sole()->validaciones()->count());
    }

    #[DataProvider('rounds')]
    public function test_iot_ingestion_before_or_after_cu23_preserves_the_committed_cut(bool $releaseFirst): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $s = $this->sensor($r);
        $evaluate = ['action' => 'evaluate', 'reservation' => $r->id];
        $ingest = ['action' => 'ingest', 'sensor' => $s->id];
        $first = $this->start([...($releaseFirst ? $evaluate : $ingest), 'hold' => $releaseFirst ? 'evaluation' : 'sensor']);
        $this->go();
        $this->waitFor('.0.held');
        $second = $this->start($releaseFirst ? $ingest : $evaluate);
        if ($releaseFirst) {
            $this->waitFor('.1.sensor');
        } else {
            $this->assertSame('deferred', $this->workerResult($second)['status']);
        }
        $this->release(0);
        $firstResult = $this->workerResult($first)['status'];
        if ($releaseFirst) {
            $this->assertSame('accepted', $this->workerResult($second)['status']);
            $this->assertSame('LIBERAR', $firstResult);
        } else {
            $this->assertSame('accepted', $firstResult);
            $this->assertSame('MANTENER_POR_USO', $this->evaluate($r)->resultado->value);
        }
        $e = EvaluacionLiberacion::sole();
        $this->assertSame($e->resultado->value === 'LIBERAR' ? 0 : 1, $e->eventos()->count());
        $this->assertSame($r->espacio_id, EventoOcupacion::sole()->espacio_historico_id);
    }

    public static function inventory(): iterable
    {
        foreach (['sensor_create', 'enable', 'disable', 'incoming', 'outgoing'] as $action) {
            yield $action.'-before' => [$action, false];
            yield $action.'-after' => [$action, true];
        }
    }

    #[DataProvider('inventory')]
    public function test_sensor_inventory_changes_serialize_in_both_orders(string $action, bool $evaluationFirst): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5, 'modo' => 'AUTOMATICA']);
        $other = EspacioAcademico::factory()->create();
        $sensor = $this->sensor($r, ['estado' => $action === 'enable' ? 'DESHABILITADO' : 'HABILITADO', 'espacio_actual_id' => $action === 'incoming' ? $other->recurso_id : $r->espacio_id]);
        $this->event($sensor, ['espacio_historico_id' => $r->espacio_id]);
        $actor = $this->user('Administrador');
        $change = ['action' => $action, 'sensor' => $sensor->id, 'actor' => $actor->id, 'space' => $action === 'outgoing' ? $other->recurso_id : $r->espacio_id];
        $evaluation = ['action' => 'evaluate', 'reservation' => $r->id];
        if ($evaluationFirst) {
            $a = $this->start([...$evaluation, 'hold' => 'evaluation']);
            $this->go();
            $this->waitFor('.0.held');
            $b = $this->start($change);
            $this->waitFor('.1.'.($action === 'sensor_create' ? 'space' : 'sensor'));
            $this->release(0);
            $expected = in_array($action, ['enable', 'incoming']) ? 'NO_DETERMINABLE' : 'LIBERAR';
            $this->assertSame($expected, $this->workerResult($a)['status']);
            $this->assertSame('ok', $this->workerResult($b)['status']);
            $e = EvaluacionLiberacion::sole();
            $this->assertSame($expected === 'LIBERAR' ? 1 : 0, $e->eventos()->count());
        } else {
            $a = $this->start([...$change, 'hold' => 'space']);
            $this->go();
            $this->waitFor('.0.held');
            $b = $this->start($evaluation);
            $this->assertSame('deferred', $this->workerResult($b)['status']);
            $this->release(0);
            $this->assertSame('ok', $this->workerResult($a)['status']);
            $e = $this->evaluate($r);
            $this->assertSame(in_array($action, ['enable', 'incoming']) ? 'LIBERAR' : 'NO_DETERMINABLE', $e->resultado->value);
        }
        $this->assertSame($r->espacio_id, EventoOcupacion::sole()->espacio_historico_id);
    }

    public function test_legal_cancellation_commits_first_and_cu23_rechecks_state(): void
    {
        $r = $this->reservation(['tolerancia_min' => 0, 'modo' => 'AUTOMATICA']);
        $a = $this->start(['action' => 'cancel', 'reservation' => $r->id, 'actor' => $this->user('Administrador')->id, 'time' => '2090-01-10 07:59:59', 'hold' => 'transition']);
        $this->go();
        $this->waitFor('.0.held');
        $b = $this->start(['action' => 'evaluate', 'reservation' => $r->id]);
        $this->assertSame('deferred', $this->workerResult($b)['status']);
        $this->release(0);
        $this->assertSame('CANCELADA', $this->workerResult($a)['status']);
        $this->assertNull($this->evaluate($r));
        $this->assertDatabaseCount('evaluaciones_liberacion', 0);
    }

    public function test_release_before_cancel_and_wait_crossing_start_do_not_allow_late_cancellation(): void
    {
        $actor = $this->user('Administrador');
        $r = $this->reservation(['tolerancia_min' => 0, 'modo' => 'AUTOMATICA']);
        $a = $this->start(['action' => 'evaluate', 'reservation' => $r->id, 'hold' => 'evaluation']);
        $this->go();
        $this->waitFor('.0.held');
        $b = $this->start(['action' => 'cancel', 'reservation' => $r->id, 'actor' => $actor->id]);
        $this->waitFor('.1.space');
        $this->release(0);
        $this->assertSame('LIBERAR', $this->workerResult($a)['status']);
        $this->assertContains($this->workerResult($b)['status'], ['conflict', 'denied']);
        $second = $this->reservation(['tolerancia_min' => 0]);
        $c = $this->start(['action' => 'cancel', 'reservation' => $second->id, 'actor' => $actor->id, 'time' => '2090-01-10 07:59:59', 'hold' => 'space', 'after_time' => '2090-01-10 08:00:00']);
        $this->waitFor('.2.held');
        $this->release(2);
        $this->assertSame('conflict', $this->workerResult($c)['status']);
        $this->assertSame('APROBADA', $second->fresh()->estado->value);
    }

    public function test_permission_revoked_while_policy_writer_waits_is_rechecked(): void
    {
        $actor = $this->user('Administrador');
        $p = PoliticaLiberacion::factory()->create();
        DB::beginTransaction();
        $role = Rol::where('nombre', 'Administrador')->lockForUpdate()->sole();
        $a = $this->start(['action' => 'policy_state', 'actor' => $actor->id, 'policy' => $p->id, 'active' => false]);
        $this->go();
        $this->waitFor('.0.security');
        $role->permisos()->detach(Permiso::where('codigo', 'liberacion.politicas.gestionar')->sole());
        DB::commit();
        $this->assertSame('denied', $this->workerResult($a)['status']);
        $this->assertTrue($p->fresh()->activa);
    }
}
