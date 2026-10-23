<?php

namespace App\Enums;

enum EstadoEquipamiento: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case PRESTADO = 'PRESTADO';
    case EN_MANTENIMIENTO = 'EN_MANTENIMIENTO';
    case BAJA = 'BAJA';
}
