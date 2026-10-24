<?php

namespace App\Enums;

enum TipoOperacionQr: string
{
    case USO_ESPACIO = 'USO_ESPACIO';
    case ENTREGA = 'ENTREGA';
    case DEVOLUCION = 'DEVOLUCION';
}
