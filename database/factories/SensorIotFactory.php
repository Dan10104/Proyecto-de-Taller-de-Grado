<?php

namespace Database\Factories;

use App\Models\EspacioAcademico;
use Illuminate\Database\Eloquent\Factories\Factory;

class SensorIotFactory extends Factory
{
    public function definition(): array
    {
        return ['espacio_actual_id' => EspacioAcademico::factory(), 'identificador' => 'SYN-'.strtoupper(fake()->unique()->uuid()), 'tipo' => 'Sensor sintético de prueba', 'estado' => 'HABILITADO'];
    }
}
