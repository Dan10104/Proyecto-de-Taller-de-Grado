<?php

namespace App\Support;

use App\Enums\RolInstitucional;

class PermissionCatalog
{
    public const DEFINITIONS = [
        'reservas.ver_propias' => ['nombre' => 'Consultar reservas propias', 'fuente' => 'RF6–7 / CU08: seguimiento del titular'],
        'reservas.ver_administrativas' => ['nombre' => 'Consultar reservas administrativas', 'fuente' => 'RF6–7 / CU08: consulta de gestión autorizada'],
        'reservas.solicitar' => ['nombre' => 'Solicitar reserva', 'fuente' => 'RF6 / CU07: Docente o Estudiante autorizado'],
        'reservas.resolver' => ['nombre' => 'Aprobar o rechazar solicitudes de reserva', 'fuente' => 'RF7 / CU09: resolver pendientes'],
        'reservas.cancelar_propias' => ['nombre' => 'Cancelar reservas propias', 'fuente' => 'RF7 / CU10: titular antes del inicio'],
        'reservas.cancelar_administrativas' => ['nombre' => 'Cancelar reservas administrativamente', 'fuente' => 'RF7 / CU10: responsable autorizado antes del inicio'],
        'espacios.ver' => ['nombre' => 'Consultar espacios y disponibilidad', 'fuente' => 'RF4–5 / CU05–06: catálogo, detalle y disponibilidad base'],
        'espacios.gestionar' => ['nombre' => 'Registrar y editar espacios', 'fuente' => 'RF4 / CU05: datos, características y catálogos auxiliares'],
        'espacios.cambiar_estado' => ['nombre' => 'Cambiar estado de espacios', 'fuente' => 'RF4 / CU05: estado operativo'],
        'espacios.gestionar_bloqueos' => ['nombre' => 'Gestionar bloqueos de espacios', 'fuente' => 'RF4–5 / CU05–06: registrar y desactivar restricciones temporales'],
        'usuarios.ver' => ['nombre' => 'Consultar usuarios', 'fuente' => 'RF2 / CU03: listar, buscar, filtrar y consultar'],
        'usuarios.crear' => ['nombre' => 'Registrar usuarios', 'fuente' => 'RF2 / CU03: registrar cuenta'],
        'usuarios.editar' => ['nombre' => 'Editar usuarios', 'fuente' => 'RF2 / CU03: actualizar datos y contraseña administrativa'],
        'usuarios.deshabilitar' => ['nombre' => 'Deshabilitar usuarios', 'fuente' => 'RF2 / CU03: baja lógica'],
        'roles.ver' => ['nombre' => 'Consultar roles', 'fuente' => 'RF3 / CU04: consultar roles y asignaciones'],
        'roles.crear' => ['nombre' => 'Registrar rol institucional', 'fuente' => 'RF3 / CU04: alta dentro del catálogo institucional'],
        'roles.editar' => ['nombre' => 'Editar roles', 'fuente' => 'RF3 / CU04: actualizar datos compatibles'],
        'permisos.ver' => ['nombre' => 'Consultar permisos', 'fuente' => 'RF3 / CU04: consultar catálogo de permisos'],
        'roles.gestionar_permisos' => ['nombre' => 'Configurar permisos de rol', 'fuente' => 'RF3 / CU04: asociar y retirar permisos'],
        'roles.asignar' => ['nombre' => 'Asignar roles', 'fuente' => 'RF2–3 / CU03–04: asignar rol a usuario'],
        'roles.retirar' => ['nombre' => 'Retirar roles', 'fuente' => 'RF3 / CU04: retirar conservando historia'],
    ];

    public static function eligibleRoles(string $code): array
    {
        if (! array_key_exists($code, self::DEFINITIONS)) {
            return [];
        }
        if (in_array($code, ['reservas.ver_propias', 'reservas.cancelar_propias', 'espacios.ver'], true)) {
            return array_column(RolInstitucional::cases(), 'value');
        }
        if ($code === 'reservas.solicitar') {
            return ['Docente', 'Estudiante'];
        }
        if (str_starts_with($code, 'espacios.') || str_starts_with($code, 'reservas.')) {
            return ['Administrador', 'Encargado de Laboratorio'];
        }

        return ['Administrador'];
    }

    public static function forRole(string $role): array
    {
        return array_values(array_filter(array_keys(self::DEFINITIONS), fn ($code) => in_array($role, self::eligibleRoles($code), true)));
    }

    // Required to recover management; ordinary admin permissions remain removable.
    public const RECOVERY = ['usuarios.ver', 'usuarios.crear', 'roles.ver', 'permisos.ver', 'roles.gestionar_permisos', 'roles.asignar'];
}
