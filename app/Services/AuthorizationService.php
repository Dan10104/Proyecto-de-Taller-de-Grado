<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Models\Usuario;
use App\Support\PermissionCatalog;

class AuthorizationService
{
    public function permissions(Usuario $user): array
    {
        if ($user->estado !== EstadoUsuario::ACTIVO) {
            return [];
        }

        return $user->rolesActivos()
            ->join('roles_permisos', 'roles_permisos.rol_id', '=', 'roles.id')
            ->join('permisos', 'permisos.id', '=', 'roles_permisos.permiso_id')
            ->whereIn('permisos.codigo', array_keys(PermissionCatalog::DEFINITIONS))
            ->distinct()->get(['roles.nombre as rol', 'permisos.codigo'])
            ->filter(fn ($row) => in_array($row->rol, PermissionCatalog::eligibleRoles($row->codigo), true))
            ->pluck('codigo')->unique()->values()->all();
    }

    public function allows(Usuario $user, string $permission): bool
    {
        return in_array($permission, $this->permissions($user), true);
    }
}
