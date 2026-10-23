<?php

namespace Database\Factories;

use App\Models\EquipamientoTecnologico;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrestamoFactory extends Factory
{
    protected $model = Prestamo::class;

    public function definition(): array
    {
        return ['equipamiento_id' => EquipamientoTecnologico::factory(), 'solicitante_id' => Usuario::factory(), 'inicio_previsto' => '2090-01-10 08:00:00', 'fin_previsto' => '2090-01-10 09:00:00', 'motivo' => 'Actividad académica sintética', 'solicitado_en' => now(), 'estado' => 'PENDIENTE'];
    }
}
