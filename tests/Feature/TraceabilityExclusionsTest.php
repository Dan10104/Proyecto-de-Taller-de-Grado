<?php

namespace Tests\Feature;

use App\Models\PoliticaLiberacion;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Services\ReservationService;
use App\Services\Traceability\LocalEvidenceService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use Tests\ReleaseTestCase;

#[Group('traceability')]
class TraceabilityExclusionsTest extends ReleaseTestCase
{
    public function test_automatic_release_preserves_approval_hash_and_does_not_create_a_fifth_action(): void
    {
        $this->travelTo(CarbonImmutable::parse('2090-01-10 07:00:00', 'America/La_Paz'));
        $r = Reserva::factory()->create();
        $r->espacio->forceFill(['politica_liberacion_id' => PoliticaLiberacion::factory()->create(['modo' => 'AUTOMATICA'])->id])->save();
        app(ReservationService::class)->approve($this->user('Administrador'), $r);
        $e = app(LocalEvidenceService::class)->ensure(RegistroActividad::sole()->id);
        $this->travelTo(CarbonImmutable::parse('2090-01-10 08:15:00', 'America/La_Paz'));
        $this->assertSame('LIBERAR', $this->evaluate($r)->resultado->value);
        $this->assertSame('LIBERADA', $r->fresh()->estado->value);
        $this->assertSame('VALIDO', app(LocalEvidenceService::class)->check($e));
        $this->assertDatabaseCount('registros_actividad', 1);
    }

    public function test_qr_iot_and_release_evidence_have_no_blockchain_side_effects(): void
    {
        $r = $this->reservation(['usa_iot' => true, 'max_antiguedad_lectura_min' => 5]);
        $this->qr($r);
        $this->event($this->sensor($r), ['tipo' => 'PRESENCIA', 'valor' => null]);
        $this->assertSame('MANTENER_POR_USO', $this->evaluate($r)->resultado->value);
        $this->assertDatabaseCount('registros_actividad', 0);
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
    }
}
