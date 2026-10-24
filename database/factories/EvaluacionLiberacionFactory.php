<?php

namespace Database\Factories;

use App\Models\PoliticaLiberacion;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionLiberacionFactory extends Factory
{
    public function definition(): array
    {
        return ['reserva_id' => Reserva::factory()->aprobada(), 'politica_id' => PoliticaLiberacion::factory(), 'fecha_hora' => now(), 'resultado' => 'NO_DETERMINABLE', 'motivo' => 'Evaluación sintética: evidencia insuficiente.'];
    }
}
