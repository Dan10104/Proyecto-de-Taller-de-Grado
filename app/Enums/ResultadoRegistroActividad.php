<?php

namespace App\Enums;

enum ResultadoRegistroActividad: string
{
    case EXITO = 'EXITO';
    case RECHAZO = 'RECHAZO';
    case ERROR = 'ERROR';
}
