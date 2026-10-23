<?php

namespace App\Enums;

enum CondicionDevolucion: string
{
    case ADECUADA = 'ADECUADA';
    case CON_INCIDENCIA = 'CON_INCIDENCIA';
    case NO_DETERMINABLE = 'NO_DETERMINABLE';
}
