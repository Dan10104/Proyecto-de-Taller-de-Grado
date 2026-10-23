<?php

namespace Database\Factories;

use App\Models\Piso;
use Illuminate\Database\Eloquent\Factories\Factory;

class PisoFactory extends Factory
{
    protected $model = Piso::class;

    public function definition(): array
    {
        return ['numero' => fake()->unique()->numberBetween(10000, 2000000), 'nombre' => 'Piso sintético '.fake()->unique()->numerify('######')];
    }
}
