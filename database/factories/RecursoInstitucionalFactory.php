<?php

namespace Database\Factories;

use App\Models\RecursoInstitucional;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecursoInstitucionalFactory extends Factory
{
    protected $model = RecursoInstitucional::class;

    public function definition(): array
    {
        return ['codigo' => 'REC-TEST-'.fake()->unique()->numerify('########'), 'nombre' => 'Recurso sintético'];
    }
}
