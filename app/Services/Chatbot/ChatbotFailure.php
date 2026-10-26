<?php

namespace App\Services\Chatbot;

use Illuminate\Http\JsonResponse;

class ChatbotFailure extends \RuntimeException
{
    public function __construct(public readonly string $reason = 'unavailable', public readonly int $httpStatus = 503, public readonly int $retryAfter = 0)
    {
        parent::__construct('Servicio de asistencia no disponible temporalmente.');
    }

    public function response(): JsonResponse
    {
        $message = match ($this->httpStatus) {
            422 => 'Escribe una consulta válida dentro del límite indicado.',
            429 => 'Alcanzaste el límite de consultas. Espera un momento e inténtalo nuevamente.',
            403 => 'Tu acceso cambió. Recarga la página para continuar.',
            401 => 'La sesión finalizó. Inicia sesión nuevamente.',
            default => $this->getMessage(),
        };

        return response()->json(['code' => $this->reason, 'message' => $message], $this->httpStatus,
            $this->retryAfter > 0 ? ['Retry-After' => (string) $this->retryAfter] : []);
    }
}
