<?php

namespace Database\Factories;

use App\Models\EquipamientoTecnologico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidenciaMantenimientoFactory extends Factory
{
    protected $model = IncidenciaMantenimiento::class;

    public function definition(): array
    {
        return ['recurso_id' => EquipamientoTecnologico::factory()->state(['estado' => 'EN_MANTENIMIENTO']), 'reportado_por_id' => Usuario::factory(), 'tipo' => 'INCIDENCIA', 'descripcion' => 'Incidencia sintética de prueba', 'inicio' => now(), 'estado' => 'ABIERTA'];
    }
}
