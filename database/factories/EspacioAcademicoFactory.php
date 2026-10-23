<?php

namespace Database\Factories;

use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\RecursoInstitucional;
use App\Models\TipoEspacio;
use Illuminate\Database\Eloquent\Factories\Factory;

class EspacioAcademicoFactory extends Factory
{
    protected $model = EspacioAcademico::class;

    public function definition(): array
    {
        return ['recurso_id' => RecursoInstitucional::factory(), 'piso_id' => Piso::factory(), 'tipo_espacio_id' => TipoEspacio::factory(), 'capacidad' => 30, 'estado' => 'DISPONIBLE'];
    }
}
