<?php

namespace App\Enums;

enum EstadoDevolucion: string
{
    case PENDIENTE_REVISION = 'PENDIENTE_REVISION';
    case CONFIRMADA = 'CONFIRMADA';
}
