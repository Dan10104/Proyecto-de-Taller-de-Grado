<?php

namespace Database\Factories;

use App\Models\EquipamientoTecnologico;
use App\Models\RecursoInstitucional;
use App\Models\TipoEquipamiento;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipamientoTecnologicoFactory extends Factory
{
    protected $model = EquipamientoTecnologico::class;

    public function definition(): array
    {
        return ['recurso_id' => RecursoInstitucional::factory(), 'tipo_equipamiento_id' => TipoEquipamiento::factory(), 'estado' => 'DISPONIBLE', 'marca' => 'Marca sintética', 'modelo' => 'Modelo de prueba'];
    }
}
