<?php

namespace Database\Factories;

use App\Models\SensorIot;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventoOcupacionFactory extends Factory
{
    public function definition(): array
    {
        return ['sensor_id' => SensorIot::factory(), 'espacio_historico_id' => fn (array $attributes) => SensorIot::findOrFail($attributes['sensor_id'])->espacio_actual_id, 'tipo' => 'PRESENCIA', 'valor' => null, 'fecha_evento' => now()->startOfSecond()];
    }
}
