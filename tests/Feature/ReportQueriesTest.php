<?php

namespace Tests\Feature;

use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\EventoOcupacion;
use App\Models\IncidenciaMantenimiento;
use App\Models\Reserva;
use App\Models\SensorIot;
use App\Services\Reporting\ReportingAccess;
use App\Services\Reporting\ReportRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReportingTestCase;

class ReportQueriesTest extends ReportingTestCase
{
    public function test_registry_has_exactly_seven_definitions_and_permissions(): void
    {
        $r = app(ReportRegistry::class);
        $this->assertSame(['reservas', 'espacios', 'equipamiento', 'prestamos', 'incidencias', 'iot', 'actividad-operacional'], array_keys($r->definitions()));
        $this->assertCount(7, $r->available(ReportingAccess::for($this->user('Administrador'))));
        $this->assertSame([], $r->available(ReportingAccess::for($this->user('Docente'))));
    }

    #[DataProvider('reports')]
    public function test_every_report_supports_empty_preview_without_activity(string $id): void
    {
        $p = $this->report($id);
        $this->assertSame(0, $p['total']);
        $this->assertSame([], $p['filas']);
        $this->assertDatabaseCount('registros_actividad', 0);
    }

    public static function reports(): array
    {
        return array_map(fn ($id) => [$id], ['reservas', 'espacios', 'equipamiento', 'prestamos', 'incidencias', 'iot', 'actividad-operacional']);
    }

    public function test_reservations_use_overlap_and_stable_order_and_only_public_projection(): void
    {
        $first = $this->reservation(['inicio' => '2090-01-09 23:00:00', 'fin' => '2090-01-10 01:00:00']);
        $second = $this->reservation();
        $this->reservation(['inicio' => '2090-01-09 23:00:00', 'fin' => '2090-01-10 00:00:00']);
        $this->reservation(['inicio' => '2090-01-11 00:00:00', 'fin' => '2090-01-11 01:00:00']);
        $p = $this->report('reservas');
        $this->assertSame(2, $p['total']);
        $this->assertSame([$first->codigo, $second->codigo], array_column($p['filas'], 'codigo'));
        $this->assertSame(['codigo', 'espacio', 'solicitante', 'inicio', 'fin', 'estado', 'solicitada_en', 'resuelta_en', 'cancelada_en'], array_keys($p['filas'][0]));
        $this->assertStringNotContainsString($first->proposito, json_encode($p));
    }

    public function test_space_availability_uses_central_rules_for_all_conflicts(): void
    {
        $spaces = EspacioAcademico::factory()->count(6)->create();
        Reserva::factory()->aprobada()->create(['espacio_id' => $spaces[0]->getKey()]);
        $this->blockFor($spaces[1], '2090-01-10 08:00', '2090-01-10 09:00');
        IncidenciaMantenimiento::factory()->create(['recurso_id' => $spaces[2]->getKey(), 'inicio' => now()->addDay()]);
        $spaces[3]->forceFill(['estado' => 'BLOQUEADO'])->save();
        $this->reservation(['espacio_id' => $spaces[4]->getKey(), 'estado' => 'LIBERADA']);
        $p = $this->report('espacios', ['vista' => 'disponibilidad', 'inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00']);
        $by = collect($p['filas'])->keyBy('codigo');
        foreach ($spaces as $index => $s) {
            $this->assertSame($index >= 4, $by[$s->recurso->codigo]['disponible']);
        }
        $p = $this->report('espacios', ['vista' => 'disponibilidad', 'inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00', 'disponible' => 'si']);
        $this->assertSame(2, $p['total']);
        $this->assertArrayNotHasKey('disponible', $this->report('espacios')['filas'][0]);
    }

    public function test_equipment_serial_requires_effective_manage_permission(): void
    {
        EquipamientoTecnologico::factory()->create(['numero_serie' => 'SERIAL-PRIVATE']);
        $a = $this->user('Encargado de Laboratorio');
        $this->assertSame('SERIAL-PRIVATE', $this->report('equipamiento', [], $a)['filas'][0]['numero_serie']);
        $this->revoke('equipamientos.gestionar');
        $p = $this->report('equipamiento', [], $a);
        $this->assertArrayNotHasKey('numero_serie', $p['filas'][0]);
        $this->assertStringNotContainsString('SERIAL-PRIVATE', json_encode($p));
    }

    public function test_overdue_is_strict_and_derived_and_loan_dates_overlap(): void
    {
        $late = $this->loanRow(['estado' => 'ENTREGADO', 'fin_previsto' => now()->subSecond()]);
        $this->loanRow(['estado' => 'ENTREGADO', 'fin_previsto' => now()]);
        $this->loanRow(['estado' => 'DEVUELTO', 'fin_previsto' => now()->subSecond()]);
        $p = $this->report('prestamos', ['vencido' => 'si']);
        $this->assertSame(1, $p['total']);
        $this->assertSame($late->codigo, $p['filas'][0]['codigo']);
        $this->assertTrue($p['filas'][0]['vencido']);
        $this->assertSame(2, $this->report('prestamos', ['vencido' => 'no'])['total']);
    }

    public function test_incidents_date_is_start_and_free_text_is_excluded(): void
    {
        IncidenciaMantenimiento::factory()->create(['descripcion' => 'PRIVATE DESCRIPTION', 'solucion' => 'PRIVATE SOLUTION']);
        IncidenciaMantenimiento::factory()->create(['inicio' => now()->subDay()]);
        $p = $this->report('incidencias');
        $this->assertSame(1, $p['total']);
        $this->assertArrayNotHasKey('descripcion', $p['filas'][0]);
        $this->assertArrayNotHasKey('solucion', $p['filas'][0]);
    }

    public function test_iot_uses_historical_space_not_current_assignment_or_reception_date(): void
    {
        $sensor = SensorIot::factory()->create();
        $old = $sensor->espacioActual;
        $event = EventoOcupacion::factory()->create(['sensor_id' => $sensor->id, 'tipo' => 'OCUPACION', 'valor' => 0.25, 'fecha_recepcion' => now()->addDay()]);
        $sensor->forceFill(['espacio_actual_id' => EspacioAcademico::factory()->create()->getKey()])->save();
        $p = $this->report('iot', ['espacio_historico_id' => $old->getKey()]);
        $this->assertSame(1, $p['total']);
        $this->assertStringContainsString($old->recurso->codigo, $p['filas'][0]['espacio_historico']);
        $this->assertSame(0.25, $p['filas'][0]['valor']);
        $this->assertSame(['espacio_historico', 'sensor', 'tipo', 'valor', 'fecha_evento', 'fecha_recepcion'], array_keys($p['filas'][0]));
    }

    public function test_activity_groups_and_excludes_unrelated_audit_and_other_exports(): void
    {
        $a = $this->user('Administrador');
        $r = $this->reservation();
        $this->activityFor($r, $a);
        $this->activityFor($r, $a);
        $this->activityFor($r, $a, ['accion' => 'LOGIN', 'descripcion' => 'PRIVATE DETAIL']);
        $p = $this->report('actividad-operacional', [], $a);
        $this->assertSame(1, $p['total']);
        $this->assertSame(2, $p['filas'][0]['cantidad']);
        $this->assertSame('RESERVAS', $p['filas'][0]['categoria']);
        $this->assertArrayNotHasKey('descripcion', $p['filas'][0]);
        $this->assertArrayNotHasKey('id', $p['filas'][0]);
    }

    public function test_activity_masks_actor_names_and_checks_domain_permissions(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $r = $this->reservation();
        $this->activityFor($r, $a);
        $p = $this->report('actividad-operacional', [], $a);
        $this->assertSame('Usuario #'.$a->id, $p['filas'][0]['responsable']);
        $this->revoke('reservas.ver_administrativas');
        $this->assertSame(0, $this->report('actividad-operacional', [], $a)['total']);
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_or_injected_filters_rejected(string $id, array $filters): void
    {
        $this->expectException(ValidationException::class);
        $this->report($id, $filters);
    }

    public static function invalidFilters(): array
    {
        return [['unknown', []], ['reservas', ['table' => 'usuarios']], ['reservas', ['orderBy' => 'id desc']], ['reservas', ['estado' => "APROBADA' OR 1=1"]], ['reservas', ['solicitante_id' => -1]], ['reservas', ['solicitante_id' => 999999999]], ['reservas', ['solicitante_id' => '10.000']], ['equipamiento', ['periodo' => 'hoy']], ['espacios', ['vista' => 'disponibilidad']], ['espacios', ['vista' => 'disponibilidad', 'inicio' => '2090-01-10T10:00', 'fin' => '2090-01-10T09:00']], ['espacios', ['vista' => 'inventario', 'disponible' => 'si']], ['iot', ['tipo' => 'ASISTENCIA']], ['actividad-operacional', ['accion' => 'LOGIN']]];
    }

    public function test_domain_permission_blocks_direct_report_even_with_report_permission(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $this->revoke('incidencias.ver');
        $this->expectException(AuthorizationException::class);
        $this->report('incidencias', [], $a);
    }
}
