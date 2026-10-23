<?php

namespace App\Enums;

enum RolInstitucional: string
{
    case ADMINISTRADOR = 'Administrador';
    case ENCARGADO = 'Encargado de Laboratorio';
    case DOCENTE = 'Docente';
    case ESTUDIANTE = 'Estudiante';

    public static function nombres(): array
    {
        return array_column(self::cases(), 'value');
    }
}
