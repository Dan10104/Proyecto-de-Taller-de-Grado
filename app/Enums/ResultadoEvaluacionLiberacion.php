<?php

namespace App\Enums;

enum ResultadoEvaluacionLiberacion: string
{
    case MANTENER_POR_USO = 'MANTENER_POR_USO';
    case NO_DETERMINABLE = 'NO_DETERMINABLE';
    case ALERTAR = 'ALERTAR';
    case LIBERAR = 'LIBERAR';
}
