<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\PoliticaLiberacion;
use App\Models\Reserva;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReleaseConcurrencyTestCase;

class ReleasePolicyConcurrencyTest extends ReleaseConcurrencyTestCase
{
    public static function order(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('order')]
    public function test_assignment_and_approval_recheck_under_space_lock(bool $approvalFirst): void
    {
        $actor = $this->user('Administrador');
        $r = Reserva::factory()->create(['inicio' => now()->addHour(), 'fin' => now()->addHours(2)]);
        $p = PoliticaLiberacion::factory()->create();
        $assign = ['action' => 'assign', 'actor' => $actor->id, 'space' => $r->espacio_id, 'policy' => $p->id];
        $approve = ['action' => 'approve', 'actor' => $actor->id, 'reservation' => $r->id];
        $first = $this->start([...($approvalFirst ? $approve : $assign), 'hold' => 'space']);
        $this->go();
        $this->waitFor('.0.held');
        $second = $this->start($approvalFirst ? $assign : $approve);
        $this->waitFor('.1.security');
        $this->release(0);
        $this->assertSame($approvalFirst ? 'APROBADA' : 'ok', $this->workerResult($first)['status']);
        $this->assertSame($approvalFirst ? 'invalid' : 'APROBADA', $this->workerResult($second)['status']);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertSame($approvalFirst ? null : $p->id, $r->espacio->fresh()->politica_liberacion_id);
    }

    #[DataProvider('order')]
    public function test_policy_deactivation_serializes_with_evaluation(bool $evaluationFirst): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $policy = $r->espacio->politica;
        $state = ['action' => 'policy_state', 'actor' => $this->user('Administrador')->id, 'policy' => $policy->id, 'active' => false];
        $evaluation = ['action' => 'evaluate', 'reservation' => $r->id];
        $first = $this->start([...($evaluationFirst ? $evaluation : $state), 'hold' => $evaluationFirst ? 'evaluation' : 'policy']);
        $this->go();
        $this->waitFor('.0.held');
        $second = $this->start($evaluationFirst ? $state : $evaluation);
        $this->waitFor('.1.policy');
        if (! $evaluationFirst) {
            $this->assertSame('deferred', $this->workerResult($second)['status']);
        }
        $this->release(0);
        $this->assertSame($evaluationFirst ? 'LIBERAR' : 'ok', $this->workerResult($first)['status']);
        if ($evaluationFirst) {
            $this->assertSame('ok', $this->workerResult($second)['status']);
        } else {
            $this->assertSame('NO_DETERMINABLE', $this->evaluate($r)->resultado->value);
        }
        $this->assertFalse($policy->fresh()->activa);
        $this->assertSame($evaluationFirst ? 'LIBERADA' : 'APROBADA', $r->fresh()->estado->value);
    }

    #[DataProvider('order')]
    public function test_reactivation_and_approval_protect_all_referencing_spaces(bool $approvalFirst): void
    {
        $actor = $this->user('Administrador');
        $r = $this->reservation(['activa' => false], ['estado' => 'PENDIENTE', 'inicio' => now()->addHour(), 'fin' => now()->addHours(2)]);
        $p = $r->espacio->politica;
        EspacioAcademico::factory()->create(['politica_liberacion_id' => $p->id]);
        $state = ['action' => 'policy_state', 'actor' => $actor->id, 'policy' => $p->id, 'active' => true];
        $approve = ['action' => 'approve', 'actor' => $actor->id, 'reservation' => $r->id];
        $first = $this->start([...($approvalFirst ? $approve : $state), 'hold' => 'space']);
        $this->go();
        $this->waitFor('.0.held');
        $second = $this->start($approvalFirst ? $state : $approve);
        $this->waitFor('.1.security');
        $this->release(0);
        $this->assertSame($approvalFirst ? 'APROBADA' : 'ok', $this->workerResult($first)['status']);
        $this->assertSame($approvalFirst ? 'invalid' : 'APROBADA', $this->workerResult($second)['status']);
        $this->assertSame(! $approvalFirst, $p->fresh()->activa);
    }

    public function test_concurrent_unique_version_collision_is_controlled_without_overwrite(): void
    {
        $input = ['action' => 'policy_create', 'actor' => $this->user('Administrador')->id, 'data' => ['nombre' => 'Única sintética', 'version' => 1, 'tolerancia_min' => 0, 'requiere_qr' => true, 'usa_iot' => false, 'max_antiguedad_lectura_min' => null, 'modo' => 'ALERTA', 'activa' => true]];
        $a = $this->start($input);
        $b = $this->start($input);
        $this->go();
        $this->assertEqualsCanonicalizing(['ok', 'invalid'], [$this->workerResult($a)['status'], $this->workerResult($b)['status']]);
        $this->assertSame(1, PoliticaLiberacion::count());
    }
}
