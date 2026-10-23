<?php

namespace App\Support;

class PermissionCatalog
{
    public const DEFINITIONS = [
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

    // Required to recover management; ordinary admin permissions remain removable.
    public const RECOVERY = ['usuarios.ver', 'usuarios.crear', 'roles.ver', 'permisos.ver', 'roles.gestionar_permisos', 'roles.asignar'];
}
