<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\EvaluacionLiberacion;
use Illuminate\Support\Facades\DB;
use Tests\ReleaseTestCase;

class ReleaseIdempotenceTest extends ReleaseTestCase
{
    public function test_no_determinable_requires_new_relevant_ids_and_links_the_entire_new_cut(): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
        $s = $this->sensor($r);
        $a = $this->event($s, ['valor' => null]);
        $e1 = $this->evaluate($r);
        $this->assertNull($this->evaluate($r));
        // Same second, different row: a genuine new committed fact, not MAX(id).
        $q = $this->qr($r, ['resultado' => 'INVALIDA']);
        $e2 = $this->evaluate($r);
        $this->assertNotSame($e1->id, $e2->id);
        $this->assertSame([$q->id], $e2->validaciones()->pluck('validaciones_qr.id')->all());
        $this->assertSame([$a->id], $e2->eventos()->pluck('eventos_ocupacion.id')->all());
        $this->assertNull($this->evaluate($r));
        $this->travel(1)->seconds();
        $this->event($s, ['valor' => -1]);
        $e3 = $this->evaluate($r);
        $this->assertSame('NO_DETERMINABLE', $e3->resultado->value);
        $this->assertNull($this->evaluate($r));
        $this->assertSame(1, $e1->eventos()->count());
        $this->assertSame(0, $e1->validaciones()->count());
        $this->assertSame(3, EvaluacionLiberacion::count());
    }

    public function test_older_lower_id_newly_visible_same_second_is_novel_and_old_dates_are_not(): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
        $foreign = EspacioAcademico::factory()->create();
        $s = $this->sensor($r, ['espacio_actual_id' => $foreign->recurso_id]);
        $a = $this->event($s, ['espacio_historico_id' => $r->espacio_id, 'valor' => null]);
        $b = $this->sensor($r);
        $this->event($b, ['valor' => null]);
        $this->evaluate($r);
        $s->forceFill(['espacio_actual_id' => $r->espacio_id])->save();
        $e = $this->evaluate($r);
        $this->assertSame(2, $e->eventos()->count());
        $this->assertContains($a->id, $e->eventos()->pluck('eventos_ocupacion.id')->all());
        $this->assertNull($this->evaluate($r));
        $this->travel(1)->seconds();
        $this->event($b, ['valor' => -1, 'fecha_evento' => now()->subSeconds(2), 'fecha_recepcion' => now()->subSeconds(2)]);
        $this->assertNull($this->evaluate($r));
    }

    public function test_future_becomes_eligible_but_stale_receipt_inventory_and_clock_alone_are_not_novelty(): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
        $s = $this->sensor($r);
        $this->evaluate($r);
        $this->event($s, ['fecha_evento' => now()->subMinutes(6)]);
        $this->assertNull($this->evaluate($r));
        $this->sensor($r);
        $this->assertNull($this->evaluate($r));
        $future = $this->event($s, ['tipo' => 'PRESENCIA', 'fecha_evento' => now()->addSeconds(2)]);
        $this->assertNull($this->evaluate($r));
        $this->travel(1)->seconds();
        $this->assertNull($this->evaluate($r));
        $this->travel(1)->seconds();
        $e = $this->evaluate($r);
        $this->assertSame('MANTENER_POR_USO', $e->resultado->value);
        $this->assertSame([$future->id], $e->eventos()->pluck('eventos_ocupacion.id')->all());
        $this->travel(10)->minutes();
        $this->qr($r);
        $this->assertNull($this->evaluate($r));
    }

    public function test_clock_backwards_defers_and_any_terminal_in_pair_prevents_reopening(): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
        $this->evaluate($r);
        $this->travel(-1)->seconds();
        $this->qr($r);
        $this->assertNull($this->evaluate($r));
        $this->travel(2)->seconds();
        $this->qr($r);
        $this->assertSame('MANTENER_POR_USO', $this->evaluate($r)->resultado->value);
        $this->travel(1)->seconds();
        EvaluacionLiberacion::factory()->create(['reserva_id' => $r->id, 'politica_id' => $r->espacio->politica_liberacion_id]);
        $this->qr($r);
        $this->assertNull($this->evaluate($r));
        $this->assertSame(3, EvaluacionLiberacion::count());
    }

    public function test_nested_transaction_is_rejected_without_consuming_evidence(): void
    {
        $r = $this->reservation();
        DB::beginTransaction();
        DB::table('reservas')->count();
        try {
            $this->evaluate($r);
            $this->fail('CU23 debe rechazar un snapshot previo.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('transacción nueva', $e->getMessage());
        }
        DB::rollBack();
        $this->assertNotNull($this->evaluate($r));
    }
}
