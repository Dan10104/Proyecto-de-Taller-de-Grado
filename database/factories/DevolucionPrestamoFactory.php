<?php

namespace Database\Factories;

use App\Models\DevolucionPrestamo;
use App\Models\EntregaPrestamo;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class DevolucionPrestamoFactory extends Factory
{
    protected $model = DevolucionPrestamo::class;

    public function definition(): array
    {
        return ['prestamo_id' => Prestamo::factory()->state(['estado' => 'ENTREGADO', 'resuelto_por_id' => Usuario::factory(), 'resuelto_en' => now()]), 'recibido_por_id' => Usuario::factory(), 'registrada_en' => now(), 'condicion' => 'NO_DETERMINABLE', 'descripcion_condicion' => 'Revisión sintética pendiente', 'estado' => 'PENDIENTE_REVISION'];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (DevolucionPrestamo $r) {
            if (! $r->prestamo->entrega()->exists()) {
                EntregaPrestamo::factory()->create(['prestamo_id' => $r->prestamo_id]);
            }
            $r->prestamo->equipamiento->forceFill(['estado' => 'EN_MANTENIMIENTO'])->save();
        });
    }
}
