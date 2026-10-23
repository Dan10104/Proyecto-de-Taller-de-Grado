<?php

namespace Database\Factories;

use App\Models\TipoEspacio;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoEspacioFactory extends Factory
{
    protected $model = TipoEspacio::class;

    public function definition(): array
    {
        return ['codigo' => 'TIPO-TEST-'.fake()->unique()->numerify('########'), 'nombre' => 'Tipo sintético'];
    }
}
