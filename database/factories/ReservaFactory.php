<?php

namespace Database\Factories;

use App\Enums\EstadoReserva;
use App\Models\EspacioAcademico;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition(): array
    {
        return ['espacio_id' => EspacioAcademico::factory(), 'solicitante_id' => Usuario::factory(), 'inicio' => '2090-01-10 08:00:00', 'fin' => '2090-01-10 09:00:00', 'proposito' => 'Actividad académica sintética de prueba.', 'solicitada_en' => now(), 'estado' => EstadoReserva::PENDIENTE];
    }

    public function aprobada(): static
    {
        return $this->state(fn () => ['estado' => EstadoReserva::APROBADA, 'resuelta_por_id' => Usuario::factory(), 'resuelta_en' => now()]);
    }
}
