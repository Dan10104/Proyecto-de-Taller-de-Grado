<?php

namespace Tests;

use App\Models\EspacioAcademico;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\ReservationService;

abstract class ReservationTestCase extends SpaceTestCase
{
    protected function requestData(EspacioAcademico $space, array $extra = []): array
    {
        return [...['espacio_id' => $space->getKey(), 'inicio' => '2090-01-10T08:00', 'fin' => '2090-01-10T09:00', 'proposito' => 'Clase sintética'], ...$extra];
    }

    protected function pending(?Usuario $owner = null, ?EspacioAcademico $space = null, array $extra = []): Reserva
    {
        return Reserva::factory()->create([...['solicitante_id' => ($owner ?? $this->user('Docente'))->id, 'espacio_id' => ($space ?? EspacioAcademico::factory()->create())->getKey()], ...$extra]);
    }

    protected function service(): ReservationService
    {
        return app(ReservationService::class);
    }
}
