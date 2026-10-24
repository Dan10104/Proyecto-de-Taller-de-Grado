<?php

namespace Database\Factories;

use App\Models\Reserva;
use App\Models\ValidacionQr;
use App\Services\QrTokenService;
use Illuminate\Database\Eloquent\Factories\Factory;

class ValidacionQrFactory extends Factory
{
    protected $model = ValidacionQr::class;

    public function definition(): array
    {
        $tokens = new QrTokenService;

        return ['codigo_qr_id' => null, 'reserva_id' => Reserva::factory(),
            'recurso_esperado_id' => fn ($a) => Reserva::findOrFail($a['reserva_id'])->espacio_id,
            'validado_por_id' => fn ($a) => Reserva::findOrFail($a['reserva_id'])->solicitante_id,
            'prestamo_id' => null, 'entrega_id' => null, 'devolucion_id' => null, 'fecha_hora' => now(),
            'resultado' => 'INVALIDA', 'tipo_operacion' => 'USO_ESPACIO', 'motivo_rechazo' => 'Código no registrado.',
            'hash_leido' => $tokens->hash($tokens->generate())];
    }
}
