<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PoliticaLiberacionFactory extends Factory
{
    public function definition(): array
    {
        return ['nombre' => 'Política sintética '.fake()->unique()->numerify('########'), 'version' => 1, 'tolerancia_min' => 5, 'requiere_qr' => true, 'usa_iot' => false, 'max_antiguedad_lectura_min' => null, 'modo' => 'ALERTA', 'activa' => true];
    }
}
