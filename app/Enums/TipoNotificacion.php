<?php

namespace App\Enums;

enum TipoNotificacion: string
{
    case SOLICITUD = 'SOLICITUD';
    case DECISION = 'DECISION';
    case CANCELACION = 'CANCELACION';
    case ENTREGA = 'ENTREGA';
    case DEVOLUCION = 'DEVOLUCION';
    case VENCIMIENTO = 'VENCIMIENTO';
    case INCIDENCIA = 'INCIDENCIA';
    case LIBERACION = 'LIBERACION';
}
