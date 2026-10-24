<?php

namespace Tests\Feature;

use App\Models\EvaluacionLiberacion;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReleaseTestCase;

class ReleaseMatrixTest extends ReleaseTestCase
{
    public static function matrix(): iterable
    {
        // Independent, literal D-08 oracle: eight cells per source profile, two modes.
        $cells = [
            [true, 'positiva', 'MANTENER_POR_USO', 'MANTENER_POR_USO'],
            [true, 'negativa', 'NO_DETERMINABLE', 'NO_DETERMINABLE'],
            [true, 'insuficiente', 'MANTENER_POR_USO', 'MANTENER_POR_USO'],
            [true, 'contradictoria', 'NO_DETERMINABLE', 'NO_DETERMINABLE'],
            [false, 'positiva', 'MANTENER_POR_USO', 'MANTENER_POR_USO'],
            [false, 'negativa', 'ALERTAR', 'LIBERAR'],
            [false, 'insuficiente', 'NO_DETERMINABLE', 'NO_DETERMINABLE'],
            [false, 'contradictoria', 'NO_DETERMINABLE', 'NO_DETERMINABLE'],
        ];
        foreach ([[true, true], [false, true], [true, false]] as [$qr, $iot]) {
            foreach ($cells as [$valid, $class, $alert, $automatic]) {
                if (! $iot && ! $valid && $class === 'insuficiente') {
                    [$alert, $automatic] = ['ALERTAR', 'LIBERAR'];
                }
                foreach (['ALERTA' => $alert, 'AUTOMATICA' => $automatic] as $mode => $expected) {
                    yield implode('-', [(int) $qr, (int) $iot, (int) $valid, $class, $mode]) => [$qr, $iot, $valid, $class, $mode, $expected];
                }
            }
        }
    }

    #[DataProvider('matrix')]
    public function test_all_frozen_matrix_cells_include_exact_evidence_and_state(bool $requiresQr, bool $usesIot, bool $valid, string $iot, string $mode, string $expected): void
    {
        $r = $this->reservation(['requiere_qr' => $requiresQr, 'usa_iot' => $usesIot, 'max_antiguedad_lectura_min' => $usesIot ? 5 : null, 'modo' => $mode]);
        $before = $r->fresh()->getRawOriginal();
        $q = $this->qr($r, ['resultado' => $valid ? 'VALIDA' : 'INVALIDA']);
        $s = $this->sensor($r);
        $events = collect();
        if ($iot === 'positiva' || $iot === 'contradictoria') {
            $events->push($this->event($s, ['tipo' => 'PRESENCIA', 'valor' => null]));
        }
        if ($iot === 'negativa' || $iot === 'contradictoria') {
            $events->push($this->event($s));
        }
        $evaluation = $this->evaluate($r);
        $this->assertSame($expected, $evaluation->resultado->value);
        $this->assertSame($expected === 'LIBERAR' ? 'LIBERADA' : 'APROBADA', $r->fresh()->estado->value);
        $this->assertSame('DISPONIBLE', $r->espacio->fresh()->estado->value);
        $this->assertSame([$q->id], $evaluation->validaciones()->pluck('validaciones_qr.id')->all());
        $this->assertEqualsCanonicalizing($events->pluck('id')->all(), $evaluation->eventos()->pluck('eventos_ocupacion.id')->all());
        $this->assertNotEmpty($evaluation->motivo);
        $this->assertLessThanOrEqual(500, mb_strlen($evaluation->motivo));
        $this->assertTrue($evaluation->fecha_hora->eq(now()));
        $this->assertSame($before, [...$r->fresh()->getRawOriginal(), 'estado' => $before['estado']]);
        $this->assertNull($this->evaluate($r));
        $this->assertSame(1, EvaluacionLiberacion::count());
    }
}
