<?php

namespace Tests\Feature;

use App\Enums\TipoOperacionQr;
use App\Models\RegistroActividad;
use App\Services\Traceability\CanonicalEvidenceBuilder;
use App\Services\Traceability\LocalEvidenceService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\QrTestCase;

#[Group('traceability')]
class TraceabilityLoanTest extends QrTestCase
{
    public function test_delivery_and_final_review_have_distinct_actors_and_stable_delivery_hash(): void
    {
        $a = $this->user('Administrador');
        $b = $this->user('Encargado de Laboratorio');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $this->assertDatabaseCount('registros_actividad', 0);
        $this->loans()->deliver($a, $p, $this->deliveryData(['observacion' => 'Observación sintética']));
        $activity = RegistroActividad::sole();
        $e = app(LocalEvidenceService::class)->ensure($activity->id);
        $this->assertCount(9, app(CanonicalEvidenceBuilder::class)->build($activity)['subject']);
        $this->loans()->receive($a, $p, ['condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisar']);
        $this->assertDatabaseCount('registros_actividad', 1);
        $this->loans()->confirmReview($b, $p, ['condicion' => 'ADECUADA', 'apto' => true]);
        $this->assertDatabaseCount('registros_actividad', 2);
        $return = RegistroActividad::orderByDesc('id')->first();
        $subject = app(CanonicalEvidenceBuilder::class)->build($return)['subject'];
        $this->assertCount(11, $subject);
        $this->assertSame((string) $a->id, $subject['recibido_por_id']);
        $this->assertSame((string) $b->id, $subject['confirmado_por_id']);
        $this->assertSame($b->id, $return->actor_id);
        $this->assertSame('VALIDO', app(LocalEvidenceService::class)->check($e->fresh()));
        $this->assertSame($p->fresh()->devolucion->getRawOriginal('confirmada_en'), $return->getRawOriginal('fecha_hora'));
    }

    public function test_qr_delivery_and_return_record_one_activity_per_business_milestone(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $p = $this->loan();
        $this->loans()->approve($a, $p);
        $token = $this->token();
        $this->code($token, ['prestamo_id' => $p->id]);
        $this->qr()->operation($a, $p, TipoOperacionQr::ENTREGA, $token, $this->deliveryData());
        $this->qr()->operation($a, $p, TipoOperacionQr::DEVOLUCION, $token, ['condicion' => 'ADECUADA']);
        $this->assertSame(['PRESTAMO_ENTREGADO', 'PRESTAMO_DEVUELTO'], RegistroActividad::orderBy('id')->pluck('accion')->all());
        $this->assertDatabaseCount('validaciones_qr', 2);
        foreach (RegistroActividad::all() as $activity) {
            $this->assertSame('VALIDO', app(LocalEvidenceService::class)->check(app(LocalEvidenceService::class)->ensure($activity->id)));
        }
    }

    public function test_failure_in_return_activity_rolls_back_return_equipment_and_incident(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $armed = true;
        DB::listen(function ($q) use (&$armed) {
            if ($armed && str_contains($q->sql, 'insert into `registros_actividad`')) {
                $armed = false;
                throw new \RuntimeException('atomic-return');
            }
        });
        try {
            $this->loans()->receive($a, $p, ['condicion' => 'CON_INCIDENCIA', 'descripcion_condicion' => 'Fallo sintético']);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('atomic-return', $e->getMessage());
        }
        $this->assertDatabaseCount('registros_actividad', 1);
        $this->assertDatabaseCount('devoluciones_prestamo', 0);
        $this->assertDatabaseCount('incidencias_mantenimiento', 0);
        $this->assertSame('ENTREGADO', $p->fresh()->estado->value);
        $this->assertSame('PRESTADO', $p->equipamiento->fresh()->estado->value);
    }

    public function test_model_guards_protect_delivery_and_confirmed_return_history(): void
    {
        $a = $this->user('Administrador');
        $p = $this->delivered($a);
        $this->loans()->receive($a, $p, ['condicion' => 'ADECUADA']);
        foreach ([$p->fresh()->entrega, $p->fresh()->devolucion] as $hito) {
            try {
                $hito->forceFill(['observacion' => 'Cambio'])->save();
                $this->fail();
            } catch (\LogicException) {
                $this->assertNotSame('Cambio', $hito->fresh()->observacion);
            }
        }
    }
}
