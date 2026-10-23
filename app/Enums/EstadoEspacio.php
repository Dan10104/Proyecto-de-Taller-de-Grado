<?php

namespace App\Enums;

enum EstadoEspacio: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case BLOQUEADO = 'BLOQUEADO';
    case EN_MANTENIMIENTO = 'EN_MANTENIMIENTO';
}
