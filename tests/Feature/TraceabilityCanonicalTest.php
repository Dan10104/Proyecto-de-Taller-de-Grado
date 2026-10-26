<?php

namespace Tests\Feature;

use App\Models\DevolucionPrestamo;
use App\Models\EntregaPrestamo;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\Prestamo;
use App\Models\RecursoInstitucional;
use App\Models\RegistroActividad;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\Traceability\CanonicalEvidenceBuilder;
use App\Services\Traceability\TraceabilityCanonicalizer;
use App\Services\Traceability\TraceabilityUnavailable;
use PHPUnit\Framework\Attributes\Group;
use Tests\SecurityTestCase;

#[Group('traceability')]
class TraceabilityCanonicalTest extends SecurityTestCase
{
    public function test_four_synthetic_golden_documents_have_exact_bytes_and_hashes_from_persisted_history(): void
    {
        Usuario::factory()->create(['id' => 1101]);
        Usuario::factory()->create(['id' => 1102]);
        RecursoInstitucional::factory()->create(['id' => 1201]);
        RecursoInstitucional::factory()->create(['id' => 1202]);
        EspacioAcademico::factory()->create(['recurso_id' => 1201]);
        EquipamientoTecnologico::factory()->create(['recurso_id' => 1202]);
        Reserva::factory()->create(['id' => 1301, 'codigo' => 'RES-GOLDEN', 'espacio_id' => 1201, 'solicitante_id' => 1102, 'resuelta_por_id' => 1101, 'resuelta_en' => '2090-01-01 08:00:00', 'cancelada_por_id' => 1101, 'cancelada_en' => '2090-01-02 09:00:00', 'motivo_cancelacion' => '', 'estado' => 'CANCELADA']);
        Prestamo::factory()->create(['id' => 1401, 'codigo' => 'PRE-GOLDEN', 'equipamiento_id' => 1202, 'solicitante_id' => 1102, 'estado' => 'DEVUELTO']);
        EntregaPrestamo::factory()->create(['id' => 1501, 'prestamo_id' => 1401, 'entregado_por_id' => 1101, 'receptor_id' => 1102, 'fecha_hora' => '2090-01-03 10:00:00', 'condicion_entrega' => '', 'observacion' => null]);
        DevolucionPrestamo::factory()->create(['id' => 1601, 'prestamo_id' => 1401, 'recibido_por_id' => 1102, 'confirmado_por_id' => 1101, 'registrada_en' => '2090-01-04 10:00:00', 'confirmada_en' => '2090-01-04 11:00:00', 'condicion' => 'ADECUADA', 'descripcion_condicion' => null, 'observacion' => '', 'estado' => 'CONFIRMADA']);
        $fixtures = [
            ['RESERVA_APROBADA', '2090-01-01 08:00:00', '{"activity_id":"1701","occurred_at":"2090-01-01T12:00:00Z","operation":"RESERVA_APROBADA","schema":"espacio-u-trace","subject":{"espacio_id":"1201","fin":"2090-01-10T13:00:00Z","inicio":"2090-01-10T12:00:00Z","reserva_codigo":"RES-GOLDEN","reserva_id":"1301","resuelta_en":"2090-01-01T12:00:00Z","resuelta_por_id":"1101"},"version":"1"}'],
            ['RESERVA_CANCELADA', '2090-01-02 09:00:00', '{"activity_id":"1702","occurred_at":"2090-01-02T13:00:00Z","operation":"RESERVA_CANCELADA","schema":"espacio-u-trace","subject":{"cancelada_en":"2090-01-02T13:00:00Z","cancelada_por_id":"1101","espacio_id":"1201","motivo_cancelacion_hash":"e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855","reserva_codigo":"RES-GOLDEN","reserva_id":"1301"},"version":"1"}'],
            ['PRESTAMO_ENTREGADO', '2090-01-03 10:00:00', '{"activity_id":"1703","occurred_at":"2090-01-03T14:00:00Z","operation":"PRESTAMO_ENTREGADO","schema":"espacio-u-trace","subject":{"condicion_entrega_hash":"e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855","entrega_id":"1501","entregado_por_id":"1101","equipamiento_id":"1202","fecha_hora":"2090-01-03T14:00:00Z","observacion_hash":null,"prestamo_codigo":"PRE-GOLDEN","prestamo_id":"1401","receptor_id":"1102"},"version":"1"}'],
            ['PRESTAMO_DEVUELTO', '2090-01-04 11:00:00', '{"activity_id":"1704","occurred_at":"2090-01-04T15:00:00Z","operation":"PRESTAMO_DEVUELTO","schema":"espacio-u-trace","subject":{"condicion":"ADECUADA","confirmada_en":"2090-01-04T15:00:00Z","confirmado_por_id":"1101","descripcion_condicion_hash":null,"devolucion_id":"1601","equipamiento_id":"1202","observacion_hash":"e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855","prestamo_codigo":"PRE-GOLDEN","prestamo_id":"1401","recibido_por_id":"1102","registrada_en":"2090-01-04T14:00:00Z"},"version":"1"}'],
        ];
        foreach ($fixtures as $i => [$operation, $date, $expected]) {
            $activity = new RegistroActividad;
            $activity->forceFill(['id' => 1701 + $i, 'accion' => $operation, 'fecha_hora' => $date, 'actor_id' => 1101, 'usuario_afectado_id' => 1102, 'recurso_id' => $i < 2 ? 1201 : 1202, 'reserva_id' => $i < 2 ? 1301 : null, 'prestamo_id' => $i < 2 ? null : 1401, 'incidencia_id' => null, 'proceso_responsable' => null, 'critica' => true, 'resultado' => 'EXITO']);
            $document = app(CanonicalEvidenceBuilder::class)->build($activity);
            $this->assertSame($expected, app(TraceabilityCanonicalizer::class)->serialize($document));
            $this->assertSame(hash('sha256', $expected), app(CanonicalEvidenceBuilder::class)->hash($activity));
            $this->assertSame($expected, app(TraceabilityCanonicalizer::class)->serialize(array_reverse($document, true)));
            $activity->forceFill(['id' => '9007199254740993']);
            $large = app(CanonicalEvidenceBuilder::class)->build($activity);
            $this->assertSame('9007199254740993', $large['activity_id']);
            $this->assertStringContainsString('"activity_id":"9007199254740993"', app(TraceabilityCanonicalizer::class)->serialize($large));
        }
    }

    public function test_php_node_boundary_preserves_jcs_numbers_unicode_and_rejects_bad_input(): void
    {
        $jcs = app(TraceabilityCanonicalizer::class);
        $this->assertSame('{"a":[100000000000000000000,1e+30,0.000001,0],"z":"'."\u{2028}".'"}', $jcs->serialize(['z' => "\u{2028}", 'a' => [1e20, 1e30, 1e-6, -0.0]]));
        $this->expectException(TraceabilityUnavailable::class);
        $jcs->serialize(['invalid' => "\xC3\x28"]);
    }
}
