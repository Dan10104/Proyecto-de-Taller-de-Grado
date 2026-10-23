<?php

namespace Database\Factories;

use App\Models\EntregaPrestamo;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntregaPrestamoFactory extends Factory
{
    protected $model = EntregaPrestamo::class;

    public function definition(): array
    {
        return ['prestamo_id' => Prestamo::factory()->state(['estado' => 'ENTREGADO', 'resuelto_por_id' => Usuario::factory(), 'resuelto_en' => now()]), 'entregado_por_id' => Usuario::factory(), 'receptor_id' => fn (array $a) => Prestamo::findOrFail($a['prestamo_id'])->solicitante_id, 'fecha_hora' => now(), 'condicion_entrega' => 'Inspección sintética adecuada'];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (EntregaPrestamo $d) {
            $d->prestamo->equipamiento->forceFill(['estado' => 'PRESTADO'])->save();
        });
    }
}
