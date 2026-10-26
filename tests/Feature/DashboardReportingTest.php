<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\EvaluacionLiberacion;
use App\Models\EventoOcupacion;
use App\Models\IncidenciaMantenimiento;
use App\Models\Notificacion;
use App\Models\Reserva;
use App\Models\SensorIot;
use App\Services\DashboardService;
use App\Services\Reporting\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ReportingTestCase;

class DashboardReportingTest extends ReportingTestCase
{
    public function test_ten_administrative_cards_and_six_charts_use_operational_counts(): void
    {
        $a = $this->user('Administrador');
        $owner = $this->user('Docente');
        $r = $this->reservation(['solicitante_id' => $owner->id]);
        Reserva::factory()->aprobada()->create(['inicio' => now(), 'fin' => now()->addHour()]);
        Reserva::factory()->aprobada()->create(['inicio' => now()->addMinute(), 'fin' => now()->addHour()]);
        Reserva::factory()->aprobada()->create(['inicio' => now()->subHour(), 'fin' => now()]);
        EvaluacionLiberacion::factory()->create(['reserva_id' => $r->id, 'resultado' => 'LIBERAR']);
        EvaluacionLiberacion::factory()->create(['reserva_id' => $r->id, 'resultado' => 'ALERTAR']);
        EvaluacionLiberacion::factory()->create(['reserva_id' => $r->id, 'resultado' => 'LIBERAR', 'fecha_hora' => now()->subDay()]);
        $this->loanRow();
        $this->loanRow(['estado' => 'ENTREGADO', 'fin_previsto' => now()]);
        $this->loanRow(['estado' => 'ENTREGADO', 'fin_previsto' => now()->subSecond()]);
        IncidenciaMantenimiento::factory()->create();
        IncidenciaMantenimiento::factory()->create(['tipo' => 'MANTENIMIENTO', 'inicio' => now()->addDay()]);
        $data = app(DashboardService::class)->read($a, []);
        $cards = collect($data['tarjetas'])->keyBy('id');
        $this->assertCount(10, $cards);
        $this->assertCount(6, $data['graficos']);
        foreach (['A1' => 1, 'A2' => 1, 'A3' => 1, 'A4' => 4, 'A5' => 1, 'A6' => 2, 'A7' => 1, 'A8' => 5, 'A9' => 2, 'A10' => 0] as $id => $n) {
            $this->assertSame($n, $cards[$id]['valor'], $id);
        }
        $charts = collect($data['graficos'])->keyBy('id');
        $this->assertSame(4, array_sum($charts['G1']['valores']));
        $this->assertSame(3, array_sum($charts['G2']['valores']));
        $this->assertSame(4, $charts['G5']['valores']['2090-01-10']);
    }

    #[DataProvider('personalRoles')]
    public function test_seven_personal_cards_exclude_other_users(string $role): void
    {
        $owner = $this->user($role);
        $other = $this->user('Docente');
        $this->reservation(['solicitante_id' => $owner->id]);
        $this->reservation(['solicitante_id' => $other->id]);
        for ($i = 0; $i < 6; $i++) {
            Reserva::factory()->aprobada()->create(['solicitante_id' => $owner->id, 'inicio' => now()->addMinutes($i), 'fin' => now()->addHour()]);
        }
        Reserva::factory()->aprobada()->create(['solicitante_id' => $other->id]);
        foreach (['PENDIENTE', 'APROBADO', 'ENTREGADO'] as $state) {
            $this->loanRow(['estado' => $state, 'solicitante_id' => $owner->id, 'fin_previsto' => now()->subSecond()]);
            $this->loanRow(['estado' => $state, 'solicitante_id' => $other->id, 'fin_previsto' => now()->subSecond()]);
        }
        Notificacion::factory()->create(['destinatario_id' => $owner->id]);
        Notificacion::factory()->create(['destinatario_id' => $owner->id, 'leida_en' => now()]);
        Notificacion::factory()->create(['destinatario_id' => $other->id]);
        $data = app(DashboardService::class)->read($owner, []);
        $cards = collect($data['tarjetas'])->keyBy('id');
        $this->assertSame('personal', $data['vista']);
        $this->assertCount(7, $cards);
        $this->assertSame([], $data['graficos']);
        $this->assertCount(5, $data['proximas']);
        foreach (['P1' => 1, 'P2' => 6, 'P3' => 1, 'P4' => 1, 'P5' => 1, 'P6' => 1, 'P7' => 1] as $id => $n) {
            $this->assertSame($n, $cards[$id]['valor']);
        }
    }

    public static function personalRoles(): array
    {
        return [['Docente'], ['Estudiante']];
    }

    public function test_domain_permissions_remove_institutional_cards_and_charts(): void
    {
        $a = $this->user('Encargado de Laboratorio');
        $this->revoke('reservas.ver_administrativas');
        $this->revoke('incidencias.ver');
        $this->revoke('iot.monitoreo.ver');
        $d = app(DashboardService::class)->read($a, []);
        $this->assertSame(['A4', 'A5', 'A6', 'A7', 'A8'], array_column($d['tarjetas'], 'id'));
        $this->assertSame(['G2', 'G3', 'G6'], array_column($d['graficos'], 'id'));
    }

    public function test_iot_counts_monitored_spaces_and_fresh_evidence_without_occupancy_inference(): void
    {
        $fresh = SensorIot::factory()->create();
        $stale = SensorIot::factory()->create();
        SensorIot::factory()->create(['estado' => 'DESHABILITADO']);
        EspacioAcademico::factory()->create();
        EventoOcupacion::factory()->create(['sensor_id' => $fresh->id, 'fecha_evento' => now(), 'fecha_recepcion' => now()]);
        EventoOcupacion::factory()->create(['sensor_id' => $stale->id, 'fecha_evento' => now()->subHour(), 'fecha_recepcion' => now()->subHour()]);
        $d = app(DashboardService::class)->read($this->user('Administrador'), []);
        $this->assertSame(['monitorizados' => 2, 'no_determinable' => 2, 'con_evidencia_reciente' => 1, 'sin_evidencia_reciente' => 1], $d['iot']);
    }

    public function test_daily_and_monthly_buckets_include_interior_zeros_and_boundaries(): void
    {
        $this->reservation(['solicitada_en' => '2090-01-01 00:00:00']);
        $this->reservation(['solicitada_en' => '2090-01-03 23:59:59']);
        $this->reservation(['solicitada_en' => '2090-01-04 00:00:00']);
        $a = $this->user('Administrador');
        $d = app(DashboardService::class)->read($a, ['periodo' => 'personalizado', 'fecha_inicio' => '2090-01-01', 'fecha_fin' => '2090-01-03']);
        $g = collect($d['graficos'])->keyBy('id');
        $this->assertSame(['2090-01-01' => 1, '2090-01-02' => 0, '2090-01-03' => 1], $g['G5']['valores']);
        $d = app(DashboardService::class)->read($a, ['periodo' => 'personalizado', 'fecha_inicio' => '2090-01-02', 'fecha_fin' => '2090-03-02']);
        $g = collect($d['graficos'])->keyBy('id');
        $this->assertSame(['2090-01' => 2, '2090-02' => 0, '2090-03' => 0], $g['G5']['valores']);
    }

    #[DataProvider('presets')]
    public function test_local_presets_and_exclusive_end(string $preset, int $days, string $start): void
    {
        $p = ReportPeriod::from(['periodo' => $preset], CarbonImmutable::now('America/La_Paz'));
        $this->assertSame($start, $p->start->toDateTimeString());
        $this->assertSame('2090-01-11 00:00:00', $p->end->toDateTimeString());
        $this->assertSame($days, $p->days());
        $this->assertCount($days, $p->buckets());
    }

    public static function presets(): array
    {
        return [['hoy', 1, '2090-01-10 00:00:00'], ['7_dias', 7, '2090-01-04 00:00:00'], ['30_dias', 30, '2089-12-12 00:00:00']];
    }

    public function test_exact_31_day_threshold_and_leap_day(): void
    {
        $p = ReportPeriod::from(['periodo' => 'personalizado', 'fecha_inicio' => '2088-02-01', 'fecha_fin' => '2088-03-02'], CarbonImmutable::now());
        $this->assertFalse($p->monthly());
        $this->assertArrayHasKey('2088-02-29', $p->buckets());
        $this->assertCount(31, $p->buckets());
        $p = ReportPeriod::from(['periodo' => 'personalizado', 'fecha_inicio' => '2088-02-01', 'fecha_fin' => '2088-03-03'], CarbonImmutable::now());
        $this->assertTrue($p->monthly());
        $this->assertSame(['2088-02' => 0, '2088-03' => 0], $p->buckets());
    }

    #[DataProvider('invalidPeriods')]
    public function test_invalid_periods_are_rejected(array $filters): void
    {
        $this->expectException(ValidationException::class);
        ReportPeriod::from($filters, CarbonImmutable::now());
    }

    public static function invalidPeriods(): array
    {
        return [[['periodo' => 'sql']], [['periodo' => 'hoy', 'fecha_inicio' => '2090-01-01']], [['periodo' => 'personalizado']], [['periodo' => 'personalizado', 'fecha_inicio' => '2090-01-02', 'fecha_fin' => '2090-01-01']], [['periodo' => 'personalizado', 'fecha_inicio' => '2090-02-30', 'fecha_fin' => '2090-03-01']]];
    }

    #[DataProvider('personalRoles')]
    public function test_http_personal_cannot_request_institutional_data_or_arbitrary_user(string $role): void
    {
        $this->login($this->user($role));
        $this->withCredentials();
        $this->get('/dashboard')->assertOk();
        $this->getJson('/dashboard?vista=administrativo')->assertForbidden();
        $this->getJson('/dashboard?user_id=123')->assertUnprocessable();
        $this->get('/reportes')->assertForbidden();
        $this->postJson('/reportes/exportar', ['reporte' => 'reservas', 'formato' => 'pdf'])->assertForbidden();
    }

    public function test_dashboard_permission_is_revocable_even_for_admin(): void
    {
        $a = $this->user('Administrador');
        $this->login($a);
        $this->revoke('dashboard.ver', 'Administrador');
        $this->get('/dashboard')->assertForbidden();
    }
}
