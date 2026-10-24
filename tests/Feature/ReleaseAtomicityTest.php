<?php

namespace Tests\Feature;

use App\Models\BloqueoEspacio;
use App\Models\IncidenciaMantenimiento;
use App\Services\SpaceAvailabilityService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReleaseTestCase;

class ReleaseAtomicityTest extends ReleaseTestCase
{
    public static function failurePoints(): array
    {
        return [['insert into `evaluaciones_liberacion`'], ['insert into `evaluaciones_validaciones_qr`'], ['insert into `evaluaciones_eventos_ocupacion`'], ['update `reservas`']];
    }

    #[DataProvider('failurePoints')]
    public function test_failure_after_each_write_rolls_back_evaluation_pivots_and_state(string $needle): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA', 'usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
        $q = $this->qr($r, ['resultado' => 'INVALIDA']);
        $event = $this->event($this->sensor($r));
        $armed = true;
        DB::listen(function ($query) use ($needle, &$armed) {
            if ($armed && str_contains($query->sql, $needle)) {
                $armed = false;
                throw new \RuntimeException('Synthetic atomic failure');
            }
        });
        try {
            $this->evaluate($r);
            $this->fail('El fallo debe revertir todo.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic atomic failure', $e->getMessage());
        }
        foreach (['evaluaciones_liberacion', 'evaluaciones_validaciones_qr', 'evaluaciones_eventos_ocupacion'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
        $this->assertSame(0, DB::transactionLevel());
        $e = $this->evaluate($r);
        $this->assertSame('LIBERAR', $e->resultado->value);
        $this->assertTrue($e->validaciones()->sole()->is($q));
        $this->assertTrue($e->eventos()->sole()->is($event));
    }

    public function test_zero_rows_updated_never_leaves_a_liberar_evaluation(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $armed = true;
        DB::listen(function ($query) use ($r, &$armed) {
            if ($armed && str_contains($query->sql, 'insert into `evaluaciones_liberacion`')) {
                $armed = false;
                DB::table('reservas')->where('id', $r->id)->update(['estado' => 'CANCELADA']);
            }
        });
        try {
            $this->evaluate($r);
            $this->fail('Cero filas debe abortar.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('no se confirmó', $e->getMessage());
        }
        $this->assertDatabaseCount('evaluaciones_liberacion', 0);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
    }

    public function test_end_crossed_after_insert_rolls_back_and_after_locks_uses_new_server_time(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        DB::listen(function ($query) use ($r) {
            if (str_contains($query->sql, 'insert into `evaluaciones_liberacion`')) {
                $this->travelTo($r->fin);
            }
        });
        $this->assertNull($this->evaluate($r));
        $this->assertDatabaseCount('evaluaciones_liberacion', 0);
        $this->assertSame('APROBADA', $r->fresh()->estado->value);
    }

    public function test_time_refresh_recomputes_window_before_decision(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $future = $this->event($this->sensor($r), ['tipo' => 'PRESENCIA', 'fecha_evento' => now()->addSecond()]);
        $armed = true;
        DB::listen(function ($query) use (&$armed) {
            if ($armed && str_contains($query->sql, 'from `eventos_ocupacion`')) {
                $armed = false;
                $this->travel(1)->seconds();
            }
        });
        $e = $this->evaluate($r);
        $this->assertSame('MANTENER_POR_USO', $e->resultado->value);
        $this->assertTrue($e->eventos()->sole()->is($future));
        $this->assertTrue($e->fecha_hora->eq(now()));
    }

    public function test_released_reservation_stops_blocking_but_other_impediments_remain(): void
    {
        $r = $this->reservation(['modo' => 'AUTOMATICA']);
        $availability = app(SpaceAvailabilityService::class);
        $available = fn () => $availability->candidates([], $r->inicio, $r->fin)->whereKey($r->espacio_id)->exists();
        $this->assertFalse($available());
        $this->evaluate($r);
        $this->assertTrue($available());
        foreach (['BLOQUEADO', 'EN_MANTENIMIENTO'] as $state) {
            $r->espacio->forceFill(['estado' => $state])->save();
            $this->assertFalse($available());
        }
        $r->espacio->forceFill(['estado' => 'DISPONIBLE'])->save();
        $block = new BloqueoEspacio;
        $block->forceFill(['espacio_id' => $r->espacio_id, 'responsable_id' => $r->solicitante_id, 'inicio' => $r->inicio, 'fin' => $r->fin, 'motivo' => 'Bloqueo sintético', 'activo' => true])->save();
        $this->assertFalse($available());
        $block->forceFill(['activo' => false])->save();
        $this->assertTrue($available());
        IncidenciaMantenimiento::factory()->create(['recurso_id' => $r->espacio_id, 'estado' => 'ABIERTA']);
        $this->assertFalse($available());
        $this->assertSame('DISPONIBLE', $r->espacio->fresh()->estado->value);
    }
}
