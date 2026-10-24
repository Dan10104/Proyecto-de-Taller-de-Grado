<?php

namespace Database\Factories;

use App\Models\CodigoQr;
use App\Models\EspacioAcademico;
use App\Services\QrTokenService;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodigoQrFactory extends Factory
{
    protected $model = CodigoQr::class;

    public function definition(): array
    {
        $tokens = new QrTokenService;

        return ['recurso_id' => EspacioAcademico::factory(), 'reserva_id' => null, 'prestamo_id' => null,
            'token_hash' => $tokens->hash($tokens->generate()), 'generada_en' => now(), 'vence_en' => null, 'activo' => true];
    }
}
