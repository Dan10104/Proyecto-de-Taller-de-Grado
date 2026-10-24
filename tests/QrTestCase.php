<?php

namespace Tests;

use App\Models\CodigoQr;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\QrTokenService;
use App\Services\QrValidationService;

abstract class QrTestCase extends LoanTestCase
{
    protected function token(): string
    {
        return app(QrTokenService::class)->generate();
    }

    protected function code(string $token, array $target, array $extra = []): CodigoQr
    {
        return CodigoQr::factory()->create(array_merge(['recurso_id' => null, 'reserva_id' => null, 'prestamo_id' => null,
            'token_hash' => hash('sha256', $token)], $target, $extra));
    }

    protected function reservation(?Usuario $owner = null, array $extra = []): Reserva
    {
        return Reserva::factory()->aprobada()->create(array_merge(['solicitante_id' => ($owner ?? $this->user('Docente'))->id,
            'inicio' => now()->startOfMinute()->subMinute(), 'fin' => now()->startOfMinute()->addHour()], $extra));
    }

    protected function qr(): QrValidationService
    {
        return app(QrValidationService::class);
    }
}
