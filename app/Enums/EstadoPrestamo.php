<?php

namespace App\Enums;

enum EstadoPrestamo: string
{
    case PENDIENTE = 'PENDIENTE';
    case APROBADO = 'APROBADO';
    case RECHAZADO = 'RECHAZADO';
    case ENTREGADO = 'ENTREGADO';
    case DEVUELTO = 'DEVUELTO';
}
