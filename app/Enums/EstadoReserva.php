<?php

namespace App\Enums;

enum EstadoReserva: string
{
    case PENDIENTE = 'PENDIENTE';
    case APROBADA = 'APROBADA';
    case RECHAZADA = 'RECHAZADA';
    case CANCELADA = 'CANCELADA';
    case LIBERADA = 'LIBERADA';
    case FINALIZADA = 'FINALIZADA';

    public function cancelable(): bool
    {
        return in_array($this, [self::PENDIENTE, self::APROBADA], true);
    }
}
