<?php

namespace Tests;

use App\Models\EventoOcupacion;
use App\Models\PoliticaLiberacion;
use App\Models\Reserva;
use App\Models\SensorIot;
use App\Models\ValidacionQr;
use App\Services\ReleaseEvaluationService;
use Carbon\CarbonImmutable;

abstract class ReleaseTestCase extends IotTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2090-01-10 08:15:00', 'America/La_Paz'));
    }

    protected function reservation(array $policy = [], array $reservation = []): Reserva
    {
        $r = Reserva::factory()->aprobada()->create($reservation);
        $p = PoliticaLiberacion::factory()->create($policy);
        $r->espacio->forceFill(['politica_liberacion_id' => $p->id])->save();

        return $r;
    }

    protected function qr(Reserva $r, array $extra = []): ValidacionQr
    {
        return ValidacionQr::factory()->create(['reserva_id' => $r->id, 'resultado' => 'VALIDA', 'motivo_rechazo' => null, ...$extra]);
    }

    protected function sensor(Reserva $r, array $extra = []): SensorIot
    {
        return SensorIot::factory()->create(['espacio_actual_id' => $r->espacio_id, ...$extra]);
    }

    protected function event(SensorIot $s, array $extra = []): EventoOcupacion
    {
        return EventoOcupacion::factory()->create(['sensor_id' => $s->id, 'tipo' => 'OCUPACION', 'valor' => 0, 'fecha_evento' => now(), 'fecha_recepcion' => now(), ...$extra]);
    }

    protected function evaluate(Reserva $r)
    {
        return app(ReleaseEvaluationService::class)->evaluate($r->id);
    }
}
