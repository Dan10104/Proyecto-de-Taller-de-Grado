<?php

namespace App\Enums;

enum ResultadoVerificacionEvidencia: string
{
    case VALIDO = 'VALIDO';
    case INCONSISTENTE = 'INCONSISTENTE';
    case PENDIENTE = 'PENDIENTE';
    case NO_VERIFICABLE = 'NO_VERIFICABLE';
}
