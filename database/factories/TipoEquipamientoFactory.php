<?php

namespace Database\Factories;

use App\Models\TipoEquipamiento;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoEquipamientoFactory extends Factory
{
    protected $model = TipoEquipamiento::class;

    public function definition(): array
    {
        return ['codigo' => 'TIP-'.bin2hex(random_bytes(5)), 'nombre' => 'Tipo sintético'];
    }
}
