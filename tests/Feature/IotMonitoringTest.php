<?php

namespace Tests\Feature;

use App\Models\EspacioAcademico;
use App\Models\EventoOcupacion;
use App\Models\SensorIot;
use App\Services\IotMonitoringService;
use App\Services\SpaceAvailabilityService;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\SecurityTestCase;

class IotMonitoringTest extends SecurityTestCase
{
    public function test_freshness_requires_both_ages_with_inclusive_boundaries_and_no_future(): void
    {
        $this->freezeSecond();
        $service = app(IotMonitoringService::class);
        foreach ([[0, 0, true], [300, 300, true], [301, 0, false], [0, 301, false], [-1, 0, false], [0, -1, false]] as [$eventAge, $receiveAge, $fresh]) {
            $s = SensorIot::factory()->create();
            EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'fecha_evento' => now()->subSeconds($eventAge), 'fecha_recepcion' => now()->subSeconds($receiveAge)]);
            $this->assertCount($fresh ? 1 : 0, $service->current([$s->espacio_actual_id])[$s->espacio_actual_id]);
        }
    }

    public function test_out_of_order_arrivals_keep_newest_measurement_and_types_separate(): void
    {
        $this->freezeSecond();
        $s = SensorIot::factory()->create();
        EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'tipo' => 'PRESENCIA', 'valor' => 7, 'fecha_evento' => now()->subSeconds(10), 'fecha_recepcion' => now()->subSeconds(9)]);
        EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'tipo' => 'PRESENCIA', 'valor' => 2, 'fecha_evento' => now()->subSeconds(20), 'fecha_recepcion' => now()]);
        EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'tipo' => 'OCUPACION', 'valor' => 0, 'fecha_evento' => now(), 'fecha_recepcion' => now()]);
        $readings = app(IotMonitoringService::class)->current([$s->espacio_actual_id])[$s->espacio_actual_id];
        $this->assertCount(2, $readings);
        $byType = array_column($readings, 'valor', 'tipo');
        ksort($byType);
        $this->assertSame(['OCUPACION' => '0.00', 'PRESENCIA' => '7.00'], $byType);
    }

    public function test_contradictory_values_including_null_never_select_arbitrary_truth(): void
    {
        $this->freezeSecond();
        foreach ([[1, 2], [null, 0]] as $values) {
            $s = SensorIot::factory()->create();
            foreach ($values as $value) {
                EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'valor' => $value, 'fecha_evento' => now(), 'fecha_recepcion' => now()]);
            }
            $current = app(IotMonitoringService::class)->availability([$s->espacio_actual_id])[$s->espacio_actual_id];
            $this->assertSame('NO_DETERMINABLE', $current['ocupacion']);
            $this->assertTrue($current['lecturas'][0]['contradiccion']);
            $this->assertNull($current['lecturas'][0]['valor']);
            $this->assertArrayNotHasKey('sensor_id', $current['lecturas'][0]);
        }
    }

    public function test_disabled_reassigned_missing_and_stale_sensors_are_not_occupancy_evidence(): void
    {
        $this->freezeSecond();
        $service = app(IotMonitoringService::class);
        $s = SensorIot::factory()->create();
        $id = $s->espacio_actual_id;
        $this->assertSame('SIN_COMUNICACION', $service->communication($s));
        $this->assertSame([], $service->current([$id])[$id]);
        EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'fecha_evento' => now(), 'fecha_recepcion' => now()]);
        $s->forceFill(['estado' => 'DESHABILITADO', 'ultima_comunicacion' => now()])->save();
        $this->assertSame([], $service->current([$id])[$id]);
        $this->assertSame('RECIENTE', $service->communication($s));
        $s->forceFill(['estado' => 'HABILITADO', 'espacio_actual_id' => EspacioAcademico::factory()->create()->recurso_id, 'ultima_comunicacion' => now()->subSeconds(301)])->save();
        $this->assertSame([], $service->current([$id])[$id]);
        $this->assertSame('SIN_COMUNICACION_RECIENTE', $service->communication($s));
        $this->assertDatabaseCount('eventos_ocupacion', 1);
    }

    public function test_monitoring_filters_half_open_period_and_paginates_immutable_history(): void
    {
        $this->login($this->user('Encargado de Laboratorio'));
        $s = SensorIot::factory()->create();
        $start = CarbonImmutable::parse('2026-01-01T08:00:00-04:00');
        for ($i = 0; $i <= 20; $i++) {
            EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'tipo' => 'PRESENCIA', 'fecha_evento' => $start->addSeconds($i)]);
        }
        EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'tipo' => 'OCUPACION', 'fecha_evento' => $start]);
        $url = '/iot/monitoreo?'.http_build_query(['sensor_id' => $s->id, 'espacio_id' => $s->espacio_actual_id, 'tipo' => 'PRESENCIA', 'desde' => '2026-01-01T12:00:00Z', 'hasta' => '2026-01-01T12:00:20Z']);
        $latest = EventoOcupacion::where('sensor_id', $s->id)->where('tipo', 'PRESENCIA')->where('fecha_evento', $start->addSeconds(19))->sole()->id;
        $this->get($url)->assertInertia(fn (Assert $v) => $v->component('Iot/Monitoring')->where('eventos.total', 20)->has('eventos.data', 15)->where('eventos.data.0.id', $latest));
        $this->get($url.'&page=2')->assertInertia(fn (Assert $v) => $v->has('eventos.data', 5));
        $this->get('/iot/monitoreo?desde=invalid')->assertSessionHasErrors('desde');
        $this->get('/iot/monitoreo?desde=2026-01-02T12:00:00Z&hasta=2026-01-01T12:00:00Z')->assertSessionHasErrors('hasta');
        $this->delete('/iot/eventos/1')->assertNotFound();
        $this->put('/iot/eventos/1', ['valor' => 1])->assertNotFound();
    }

    public function test_cu06_is_minimal_and_base_availability_reservations_and_policy_are_unchanged(): void
    {
        $s = SensorIot::factory()->create();
        $space = $s->espacioActual;
        $base = app(SpaceAvailabilityService::class);
        $start = CarbonImmutable::now()->addDay()->startOfDay()->addHours(8);
        $before = $base->candidates([], $start, $start->addHour())->pluck('recurso_id')->all();
        EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'valor' => 0, 'fecha_evento' => now(), 'fecha_recepcion' => now()]);
        $after = $base->candidates([], $start, $start->addHour())->pluck('recurso_id')->all();
        $this->assertSame($before, $after);
        $this->login($this->user('Estudiante'));
        $url = '/disponibilidad?'.http_build_query(['fecha' => $start->toDateString(), 'hora_inicio' => '08:00', 'hora_fin' => '09:00']);
        $this->get($url)->assertInertia(fn (Assert $v) => $v->where('iot.'.$space->recurso_id.'.ocupacion', 'NO_DETERMINABLE')->missing('iot.'.$space->recurso_id.'.lecturas.0.sensor_id')->has('iot.'.$space->recurso_id.'.lecturas', 1));
        $this->assertSame('DISPONIBLE', $space->fresh()->estado->value);
        $this->assertNull($space->fresh()->politica_liberacion_id);
        $this->assertDatabaseCount('reservas', 0);
        config(['iot.monitoring_freshness_seconds' => 1]);
        $this->travel(2)->seconds();
        $this->assertSame([], app(IotMonitoringService::class)->availability([$space->recurso_id])[$space->recurso_id]['lecturas']);
        $this->assertNull($space->fresh()->politica_liberacion_id);
    }
}
