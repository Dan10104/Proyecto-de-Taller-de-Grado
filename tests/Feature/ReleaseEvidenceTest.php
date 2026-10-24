<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\EvaluacionLiberacion;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReleaseTestCase;

class ReleaseEvidenceTest extends ReleaseTestCase
{
    public static function eligibility(): array
    {
        return [['PENDIENTE', 0, '08:15:00', false], ['RECHAZADA', 0, '08:15:00', false], ['CANCELADA', 0, '08:15:00', false], ['LIBERADA', 0, '08:15:00', false], ['FINALIZADA', 0, '08:15:00', false], ['APROBADA', 15, '08:14:59', false], ['APROBADA', 15, '08:15:00', true], ['APROBADA', 0, '08:00:00', true], ['APROBADA', 0, '07:59:59', false], ['APROBADA', 0, '08:59:59', true], ['APROBADA', 0, '09:00:00', false], ['APROBADA', 60, '08:59:59', false], ['APROBADA', 61, '08:59:59', false], ['APROBADA', 2147483647, '08:15:00', false]];
    }

    #[DataProvider('eligibility')]
    public function test_eligibility_and_overflow_boundaries(string $state, int $tolerance, string $time, bool $eligible): void
    {
        $r = $this->reservation(['tolerancia_min' => $tolerance], ['estado' => $state]);
        $this->travelTo(now()->setTimeFromTimeString($time));
        $e = $this->evaluate($r);
        $this->assertSame($eligible, $e !== null);
        $this->assertSame($state, $r->fresh()->estado->value);
        $this->assertDatabaseCount('evaluaciones_liberacion', $eligible ? 1 : 0);
    }

    public function test_no_policy_is_an_ordinary_omission_and_inactive_invalid_configs_diagnose_once(): void
    {
        $r = Reserva::factory()->aprobada()->create();
        $this->assertNull($this->evaluate($r));
        foreach ([['activa' => false], ['requiere_qr' => false, 'usa_iot' => false], ['usa_iot' => true, 'max_antiguedad_lectura_min' => null], ['max_antiguedad_lectura_min' => 5]] as $config) {
            $r = $this->reservation($config);
            $q = $this->qr($r);
            $s = $this->sensor($r);
            $this->event($s);
            $e = $this->evaluate($r);
            $this->assertSame('NO_DETERMINABLE', $e->resultado->value);
            $this->assertStringContainsString('configuración', $e->motivo);
            $this->assertSame(0, $e->validaciones()->count());
            $this->assertSame(0, $e->eventos()->count());
            $this->travel(1)->seconds();
            $this->qr($r);
            $this->assertNull($this->evaluate($r));
        }
        $this->assertDatabaseCount('evaluaciones_liberacion', 4);
    }

    public function test_qr_exact_window_context_invalid_attempts_and_historical_validity(): void
    {
        $r = $this->reservation();
        $q1 = $this->qr($r, ['fecha_hora' => $r->inicio]);
        $q2 = $this->qr($r, ['resultado' => 'INVALIDA']);
        $this->qr($r, ['fecha_hora' => $r->inicio->subSecond()]);
        $this->qr($r, ['fecha_hora' => now()->addSecond()]);
        $this->qr($r, ['recurso_esperado_id' => EspacioAcademico::factory()->create()->recurso_id]);
        $this->qr(Reserva::factory()->aprobada()->create(['espacio_id' => $r->espacio_id]));
        // No active QR code is needed: immutable validation history is authoritative.
        $e = $this->evaluate($r);
        $this->assertSame('MANTENER_POR_USO', $e->resultado->value);
        $this->assertEqualsCanonicalizing([$q1->id, $q2->id], $e->validaciones()->pluck('validaciones_qr.id')->all());
    }

    public static function interpretations(): array
    {
        return [['PRESENCIA', null, 'MANTENER_POR_USO'], ['PRESENCIA', 0, 'MANTENER_POR_USO'], ['PRESENCIA', -4, 'MANTENER_POR_USO'], ['OCUPACION', 0.01, 'MANTENER_POR_USO'], ['OCUPACION', 0, 'LIBERAR'], ['OCUPACION', null, 'NO_DETERMINABLE'], ['OCUPACION', -1, 'NO_DETERMINABLE']];
    }

    #[DataProvider('interpretations')]
    public function test_iot_interpretation_is_specific_to_cu23(string $type, mixed $value, string $expected): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 10, 'modo' => 'AUTOMATICA']);
        $s = $this->sensor($r, ['tipo' => 'Texto libre que no determina capacidad']);
        $event = $this->event($s, ['tipo' => $type, 'valor' => $value]);
        $e = $this->evaluate($r);
        $this->assertSame($expected, $e->resultado->value);
        $this->assertTrue($e->eventos()->sole()->is($event));
    }

    public static function windows(): array
    {
        return [[true, 0, 0, 0, true], [true, 0, -1, 0, false], [true, 5, -300, 0, true], [true, 5, -301, 0, false], [true, 5, 1, 0, false], [true, 5, 0, 1, false], [true, 5, -1, -901, true], [false, null, -300, 0, true], [false, null, -301, 0, false], [true, 2147483647, -900, 0, true], [true, 2147483647, -901, 0, false]];
    }

    #[DataProvider('windows')]
    public function test_iot_window_bounds_and_optional_veto_are_independent_of_cu22(bool $iot, ?int $age, int $eventOffset, int $receivedOffset, bool $included): void
    {
        config(['iot.monitoring_freshness_seconds' => 1]);
        $r = $this->reservation(['usa_iot' => $iot, 'max_antiguedad_lectura_min' => $age, 'modo' => 'AUTOMATICA']);
        $event = $this->event($this->sensor($r), ['tipo' => 'PRESENCIA', 'fecha_evento' => now()->addSeconds($eventOffset), 'fecha_recepcion' => now()->addSeconds($receivedOffset)]);
        $e = $this->evaluate($r);
        $this->assertSame($included ? 'MANTENER_POR_USO' : ($iot ? 'NO_DETERMINABLE' : 'LIBERAR'), $e->resultado->value);
        $this->assertSame($included ? [$event->id] : [], $e->eventos()->pluck('eventos_ocupacion.id')->all());
    }

    public function test_complete_sensor_inventory_missing_disabled_foreign_and_ambiguous(): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
        $a = $this->sensor($r);
        $b = $this->sensor($r);
        $c = $this->sensor($r, ['estado' => 'DESHABILITADO']);
        $this->event($c, ['tipo' => 'PRESENCIA']);
        $this->event($a, ['espacio_historico_id' => EspacioAcademico::factory()->create()->recurso_id, 'tipo' => 'PRESENCIA']);
        $first = $this->event($a);
        $e = $this->evaluate($r);
        $this->assertSame('NO_DETERMINABLE', $e->resultado->value);
        $this->assertSame([$first->id], $e->eventos()->pluck('eventos_ocupacion.id')->all());
        $second = $this->event($b);
        $e = $this->evaluate($r);
        $this->assertSame('ALERTAR', $e->resultado->value);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $e->eventos()->pluck('eventos_ocupacion.id')->all());
    }

    public static function contradictions(): array
    {
        return [[false, 'PRESENCIA', 1, 'OCUPACION', 0, 1], [true, 'OCUPACION', 0, 'OCUPACION', 1, 2], [false, 'OCUPACION', null, 'OCUPACION', 0, 0], [false, 'OCUPACION', -1, 'OCUPACION', -2, 0], [false, 'PRESENCIA', null, 'PRESENCIA', 0, 0]];
    }

    #[DataProvider('contradictions')]
    public function test_contradictions_across_time_sensors_and_same_instant_dominate_qr(bool $other, string $typeA, mixed $valueA, string $typeB, mixed $valueB, int $seconds): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5, 'modo' => 'AUTOMATICA']);
        $a = $this->sensor($r);
        $b = $other ? $this->sensor($r) : $a;
        $this->event($a, ['tipo' => $typeA, 'valor' => $valueA, 'fecha_evento' => now()->subSeconds($seconds)]);
        $this->event($b, ['tipo' => $typeB, 'valor' => $valueB]);
        $this->qr($r);
        $e = $this->evaluate($r);
        $this->assertSame('NO_DETERMINABLE', $e->resultado->value);
        $this->assertSame(2, $e->eventos()->count());
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
    }

    public function test_positive_dominates_another_missing_sensor_and_partial_zero_does_not_contradict_valid_qr(): void
    {
        foreach ([false, true] as $validQr) {
            $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
            $this->sensor($r);
            $this->event($this->sensor($r), ['valor' => $validQr ? 0 : 1]);
            if ($validQr) {
                $this->qr($r);
            }
            $this->assertSame('MANTENER_POR_USO', $this->evaluate($r)->resultado->value);
        }
    }

    public function test_query_failure_is_not_empty_evidence(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $fail = true;
        DB::connection()->beforeExecuting(function ($sql) use (&$fail) {
            if ($fail && str_contains($sql, 'from `validaciones_qr`')) {
                $fail = false;
                throw new \RuntimeException('Synthetic evidence read failure');
            }
        });
        try {
            $this->evaluate($r);
            $this->fail('Debe abortar la lectura incompleta.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic evidence read failure', $e->getMessage());
        }
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertSame(0, EvaluacionLiberacion::count());
        $this->assertSame(0, DB::transactionLevel());
    }
}
