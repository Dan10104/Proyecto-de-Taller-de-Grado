<?php

namespace Tests\Feature;

use App\Models\RegistroActividad;
use App\Services\Traceability\CanonicalEvidenceBuilder;
use App\Services\Traceability\LocalEvidenceService;
use Illuminate\Support\Facades\DB;
use Tests\ReservationTestCase;

class TraceabilityReservationTest extends ReservationTestCase
{
    public function test_approval_and_cancellation_create_exact_activities_and_immutable_distinct_evidence(): void
    {
        $admin = $this->user('Administrador');
        $r = $this->pending();
        $this->service()->approve($admin, $r);
        $a = RegistroActividad::sole();
        $this->assertSame('RESERVA_APROBADA', $a->accion);
        $this->assertSame($admin->id, $a->actor_id);
        $this->assertSame($r->solicitante_id, $a->usuario_afectado_id);
        $this->assertNull($a->proceso_responsable);
        $this->assertNull($a->descripcion);
        $this->assertNull($a->incidencia_id);
        $this->assertNull($a->prestamo_id);
        $this->assertSame($r->fresh()->getRawOriginal('resuelta_en'), $a->getRawOriginal('fecha_hora'));
        $e = app(LocalEvidenceService::class)->ensure($a->id);
        $original = $e->getAttributes();
        $this->service()->cancel($admin, $r, 'Cancelación sintética');
        $this->assertDatabaseCount('registros_actividad', 2);
        $this->assertSame('VALIDO', app(LocalEvidenceService::class)->check($e->fresh()));
        $this->assertSame($original, app(LocalEvidenceService::class)->ensure($a->id)->getAttributes());
        $cancel = RegistroActividad::orderByDesc('id')->first();
        $second = app(LocalEvidenceService::class)->ensure($cancel->id);
        $this->assertNotSame($e->hash, $second->hash);
        $subject = app(CanonicalEvidenceBuilder::class)->build($cancel)['subject'];
        $this->assertSame(hash('sha256', 'Cancelación sintética'), $subject['motivo_cancelacion_hash']);
        $this->assertCount(6, $subject);
        $this->assertDatabaseCount('evidencias_trazabilidad', 2);
        $this->assertSame('PENDIENTE', $second->resultado_verificacion->value);
        $this->assertNull($second->ultima_verificacion);
    }

    public function test_pending_cancellation_has_evidence_but_rejection_has_none(): void
    {
        $admin = $this->user('Administrador');
        $r = $this->pending();
        $this->service()->cancel($admin, $r);
        $a = RegistroActividad::sole();
        $this->assertNull(app(CanonicalEvidenceBuilder::class)->build($a)['subject']['motivo_cancelacion_hash']);
        $this->service()->reject($admin, $this->pending(), 'No disponible');
        $this->assertDatabaseCount('registros_actividad', 1);
    }

    public function test_failed_critical_insert_rolls_back_business_and_has_no_evidence(): void
    {
        $r = $this->pending();
        $admin = $this->user('Administrador');
        $armed = true;
        DB::listen(function ($q) use (&$armed) {
            if ($armed && str_contains($q->sql, 'insert into `registros_actividad`')) {
                $armed = false;
                throw new \RuntimeException('synthetic');
            }
        });
        try {
            $this->service()->approve($admin, $r);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('synthetic', $e->getMessage());
        }
        $this->assertSame('PENDIENTE', $r->fresh()->estado->value);
        $this->assertDatabaseCount('registros_actividad', 0);
        $this->assertDatabaseCount('evidencias_trazabilidad', 0);
    }

    public function test_reconciler_repairs_missing_evidence_without_replaying_business_or_backfilling_history(): void
    {
        $this->service()->approve($this->user('Administrador'), $this->pending());
        // Simulate the commit-to-local-evidence crash window.
        DB::table('evidencias_trazabilidad')->delete();
        $this->artisan('trazabilidad:reconciliar')->assertSuccessful();
        $this->assertDatabaseCount('evidencias_trazabilidad', 1);
        $this->artisan('trazabilidad:reconciliar')->assertSuccessful();
        $this->assertDatabaseCount('evidencias_trazabilidad', 1);
        $this->assertDatabaseCount('registros_actividad', 1);
    }

    public function test_secondary_hashes_preserve_bytes_and_null_empty_unicode_are_distinct(): void
    {
        $builder = app(CanonicalEvidenceBuilder::class);
        $this->assertNull($builder->secondary(null));
        foreach (['', ' ', "x\n", "x\r\n", 'é', "e\u{0301}"] as $text) {
            $this->assertSame(hash('sha256', $text), $builder->secondary($text));
        }
        $this->assertNotSame($builder->secondary('é'), $builder->secondary("e\u{0301}"));
    }
}
