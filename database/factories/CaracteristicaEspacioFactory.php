<?php

namespace Database\Factories;

use App\Models\CaracteristicaEspacio;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaracteristicaEspacioFactory extends Factory
{
    protected $model = CaracteristicaEspacio::class;

    public function definition(): array
    {
        return ['nombre' => 'Característica sintética '.fake()->unique()->numerify('########'), 'descripcion' => 'Solo datos de prueba.'];
    }
}
