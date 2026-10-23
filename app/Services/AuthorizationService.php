<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Enums\RolInstitucional;
use App\Models\Usuario;
use App\Support\PermissionCatalog;

class AuthorizationService
{
    public function permissions(Usuario $user): array
    {
        if ($user->estado !== EstadoUsuario::ACTIVO) {
            return [];
        }

        return $user->rolesActivos()->where('roles.nombre', RolInstitucional::ADMINISTRADOR->value)
            ->join('roles_permisos', 'roles_permisos.rol_id', '=', 'roles.id')
            ->join('permisos', 'permisos.id', '=', 'roles_permisos.permiso_id')
            ->whereIn('permisos.codigo', array_keys(PermissionCatalog::DEFINITIONS))
            ->distinct()->pluck('permisos.codigo')->all();
    }

    public function allows(Usuario $user, string $permission): bool
    {
        return in_array($permission, $this->permissions($user), true);
    }
}
