<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Enums\RolInstitucional;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdministrationService
{
    public function change(Usuario $actor, string $permission, callable $operation): mixed
    {
        return DB::transaction(function () use ($actor, $permission, $operation) {
            $admin = Rol::where('nombre', RolInstitucional::ADMINISTRADOR->value)->lockForUpdate()->firstOrFail();
            // Re-authorize after taking the shared management lock.
            Gate::forUser($actor->fresh())->authorize($permission);
            $result = $operation();
            $codes = $admin->permisos()->pluck('codigo')->all();
            $capable = $admin->usuariosActivos()->where('estado', EstadoUsuario::ACTIVO->value)->exists();
            if (! $capable || array_diff(PermissionCatalog::RECOVERY, $codes)) {
                throw ValidationException::withMessages(['administracion' => 'Debe conservarse un administrador activo y los permisos necesarios para recuperar la administración.']);
            }

            return $result;
        }, 3);
    }

    public function createUser(Usuario $actor, array $data): Usuario
    {
        return $this->change($actor, 'usuarios.crear', function () use ($actor, $data) {
            $user = new Usuario;
            $user->fill(array_intersect_key($data, array_flip(['nombres', 'apellidos', 'correo'])));
            $user->forceFill(['password_hash' => Hash::make($data['password']), 'estado' => EstadoUsuario::ACTIVO, 'fecha_registro' => now()])->save();
            foreach ($data['roles'] ?? [] as $id) {
                Gate::forUser($actor->fresh())->authorize('roles.asignar');
                $this->assignLocked($actor, $user, Rol::findOrFail($id));
            }

            return $user;
        });
    }

    public function updateUser(Usuario $actor, Usuario $target, array $data): void
    {
        $this->change($actor, 'usuarios.editar', function () use ($target, $data) {
            $user = Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $user->fill(array_intersect_key($data, array_flip(['nombres', 'apellidos', 'correo'])));
            if (! empty($data['password'])) {
                $user->forceFill(['password_hash' => Hash::make($data['password'])]);
                $user->sesiones()->whereNull('revocada_en')->update(['revocada_en' => now()]);
            }
            $user->save();
        });
    }

    public function disableUser(Usuario $actor, Usuario $target): void
    {
        $this->change($actor, 'usuarios.deshabilitar', function () use ($target) {
            $user = Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $user->forceFill(['estado' => EstadoUsuario::INACTIVO])->save();
            $user->sesiones()->whereNull('revocada_en')->update(['revocada_en' => now()]);
        });
    }

    private function assignLocked(Usuario $actor, Usuario $target, Rol $role): void
    {
        Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
        if (AsignacionRol::where('usuario_id', $target->id)->where('rol_id', $role->id)->activas()->exists()) {
            throw ValidationException::withMessages(['rol_id' => 'El usuario ya tiene una asignación activa de este rol.']);
        }
        (new AsignacionRol)->forceFill(['usuario_id' => $target->id, 'rol_id' => $role->id, 'asignado_por_id' => $actor->id, 'fecha_asignacion' => now()])->save();
    }

    public function assign(Usuario $actor, Usuario $target, Rol $role): void
    {
        $this->change($actor, 'roles.asignar', fn () => $this->assignLocked($actor, $target, $role));
    }

    public function withdraw(Usuario $actor, Usuario $target, Rol $role): void
    {
        $this->change($actor, 'roles.retirar', function () use ($actor, $target, $role) {
            Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
            AsignacionRol::where('usuario_id', $target->id)->where('rol_id', $role->id)->activas()
                ->update(['fecha_retiro' => now(), 'retirado_por_id' => $actor->id]);
        });
    }

    public function saveRole(Usuario $actor, ?Rol $role, array $data): Rol
    {
        return $this->change($actor, $role ? 'roles.editar' : 'roles.crear', function () use ($role, $data) {
            if ($role && $role->nombre !== $data['nombre']) {
                throw ValidationException::withMessages(['nombre' => 'La identidad institucional del rol se conserva; puede editar su descripción.']);
            }
            $role ??= new Rol;
            $role->fill(array_intersect_key($data, array_flip(['nombre', 'descripcion'])))->save();

            return $role;
        });
    }

    public function setPermissions(Usuario $actor, Rol $target, array $ids): void
    {
        $this->change($actor, 'roles.gestionar_permisos', function () use ($target, $ids) {
            $role = Rol::whereKey($target->id)->lockForUpdate()->firstOrFail();
            if ($ids && $role->nombre !== RolInstitucional::ADMINISTRADOR->value) {
                throw ValidationException::withMessages(['permisos' => 'Los permisos de esta fase corresponden al Administrador, iniciador de CU03 y CU04.']);
            }
            $role->permisos()->sync($ids);
        });
    }
}
