<?php

namespace App\Services;

use App\Enums\ActivityAction as Action;
use App\Enums\EstadoUsuario;
use App\Enums\RolInstitucional;
use App\Models\AsignacionRol;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Activity\ActivityAttempt;
use App\Services\Activity\ActivityRecorder;
use App\Services\Activity\ActivityTransaction;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdministrationService
{
    public function change(Usuario $actor, string $permission, callable $operation, ?ActivityAttempt $audit = null): mixed
    {
        return app(ActivityTransaction::class)->run(function () use ($actor, $permission, $operation, $audit) {
            $admin = Rol::where('nombre', RolInstitucional::ADMINISTRADOR->value)->lockForUpdate()->firstOrFail();
            // Re-authorize after taking the shared management lock.
            Gate::forUser($actor->fresh())->authorize($permission);
            $result = $operation();
            $codes = $admin->permisos()->pluck('codigo')->all();
            $capable = $admin->usuariosActivos()->where('estado', EstadoUsuario::ACTIVO->value)->exists();
            if (! $capable || array_diff(PermissionCatalog::RECOVERY, $codes)) {
                $error = ValidationException::withMessages(['administracion' => 'Debe conservarse un administrador activo y los permisos necesarios para recuperar la administración.']);
                if ($audit) {
                    $audit->reject($error);
                }
                throw $error;
            }

            return $result;
        }, $audit);
    }

    public function createUser(Usuario $actor, array $data): Usuario
    {
        $audit = new ActivityAttempt($actor);

        return $this->change($actor, 'usuarios.crear', function () use ($actor, $data, $audit) {
            $audit->admit(Action::USUARIO_CREADO);
            $user = new Usuario;
            $user->fill(array_intersect_key($data, array_flip(['nombres', 'apellidos', 'correo'])));
            $user->forceFill(['password_hash' => Hash::make($data['password']), 'estado' => EstadoUsuario::ACTIVO, 'fecha_registro' => now()])->save();
            app(ActivityRecorder::class)->success(Action::USUARIO_CREADO, $actor, ['usuario_afectado_id' => (int) $user->id]);
            foreach ($data['roles'] ?? [] as $id) {
                Gate::forUser($actor->fresh())->authorize('roles.asignar');
                $this->assignLocked($actor, $user, Rol::findOrFail($id));
            }

            return $user;
        }, $audit);
    }

    public function updateUser(Usuario $actor, Usuario $target, array $data): void
    {
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'usuarios.editar', function () use ($actor, $target, $data, $audit) {
            $user = Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $audit->admit(Action::USUARIO_EDITADO, ['usuario_afectado_id' => (int) $user->id]);
            $revoked = 0;
            $user->fill(array_intersect_key($data, array_flip(['nombres', 'apellidos', 'correo'])));
            if (! empty($data['password'])) {
                $user->forceFill(['password_hash' => Hash::make($data['password'])]);
                $revoked = $user->sesiones()->whereNull('revocada_en')->update(['revocada_en' => now()]);
            }
            $fields = array_map(fn ($field) => $field === 'password_hash' ? 'password' : $field, array_keys($user->getDirty()));
            $user->save();
            if ($fields) {
                app(ActivityRecorder::class)->success(Action::USUARIO_EDITADO, $actor, $audit->context, ['campos' => $fields, 'sesiones_revocadas' => $revoked]);
            }
        }, $audit);
    }

    public function disableUser(Usuario $actor, Usuario $target): void
    {
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'usuarios.deshabilitar', function () use ($actor, $target, $audit) {
            $user = Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $audit->admit(Action::USUARIO_DESHABILITADO, ['usuario_afectado_id' => (int) $user->id]);
            if ($user->estado === EstadoUsuario::INACTIVO) {
                return;
            }
            $user->forceFill(['estado' => EstadoUsuario::INACTIVO])->save();
            $user->sesiones()->whereNull('revocada_en')->update(['revocada_en' => now()]);
            app(ActivityRecorder::class)->success(Action::USUARIO_DESHABILITADO, $actor, $audit->context, ['anterior' => 'ACTIVO', 'nuevo' => 'INACTIVO']);
        }, $audit);
    }

    private function assignLocked(Usuario $actor, Usuario $target, Rol $role): void
    {
        Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
        if (AsignacionRol::where('usuario_id', $target->id)->where('rol_id', $role->id)->activas()->exists()) {
            throw ValidationException::withMessages(['rol_id' => 'El usuario ya tiene una asignación activa de este rol.']);
        }
        $assignment = new AsignacionRol;
        $assignment->forceFill(['usuario_id' => $target->id, 'rol_id' => $role->id, 'asignado_por_id' => $actor->id, 'fecha_asignacion' => now()])->save();
        app(ActivityRecorder::class)->success(Action::ROL_ASIGNADO, $actor, ['usuario_afectado_id' => (int) $target->id], ['id' => (int) $role->id, 'asignacion_id' => (int) $assignment->id]);
    }

    public function assign(Usuario $actor, Usuario $target, Rol $role): void
    {
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'roles.asignar', function () use ($actor, $target, $role, $audit) {
            Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
            Rol::whereKey($role->id)->firstOrFail();
            $audit->admit(Action::ROL_ASIGNADO, ['usuario_afectado_id' => (int) $target->id]);
            $this->assignLocked($actor, $target, $role);
        }, $audit);
    }

    public function withdraw(Usuario $actor, Usuario $target, Rol $role): void
    {
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'roles.retirar', function () use ($actor, $target, $role, $audit) {
            Usuario::whereKey($target->id)->lockForUpdate()->firstOrFail();
            Rol::whereKey($role->id)->firstOrFail();
            $audit->admit(Action::ROL_RETIRADO, ['usuario_afectado_id' => (int) $target->id]);
            $count = AsignacionRol::where('usuario_id', $target->id)->where('rol_id', $role->id)->activas()
                ->update(['fecha_retiro' => now(), 'retirado_por_id' => $actor->id]);
            if ($count) {
                app(ActivityRecorder::class)->success(Action::ROL_RETIRADO, $actor, $audit->context, ['id' => (int) $role->id, 'cantidad' => $count]);
            }
        }, $audit);
    }

    public function saveRole(Usuario $actor, ?Rol $role, array $data): Rol
    {
        $audit = new ActivityAttempt($actor);

        return $this->change($actor, $role ? 'roles.editar' : 'roles.crear', function () use ($actor, $role, $data, $audit) {
            $creating = $role === null;
            $role = $role ? Rol::whereKey($role->id)->lockForUpdate()->firstOrFail() : null;
            if ($role && $role->nombre !== $data['nombre']) {
                throw ValidationException::withMessages(['nombre' => 'La identidad institucional del rol se conserva; puede editar su descripción.']);
            }
            $audit->admit($creating ? Action::ROL_CREADO : Action::ROL_EDITADO);
            $role ??= new Rol;
            $role->fill(array_intersect_key($data, array_flip(['nombre', 'descripcion'])));
            $changed = $role->isDirty();
            $role->save();
            if ($creating || $changed) {
                app(ActivityRecorder::class)->success($audit->action, $actor, [], ['id' => (int) $role->id, 'campos' => $creating ? [] : ['descripcion']]);
            }

            return $role;
        }, $audit);
    }

    public function setPermissions(Usuario $actor, Rol $target, array $ids): void
    {
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'roles.gestionar_permisos', function () use ($actor, $target, $ids, $audit) {
            $role = Rol::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $codes = Permiso::whereIn('id', $ids)->pluck('codigo')->all();
            if (count($codes) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['permisos' => 'Permiso inexistente.']);
            }
            $audit->admit(Action::PERMISOS_ROL_ACTUALIZADOS);
            if (array_diff($codes, PermissionCatalog::forRole($role->nombre))) {
                $audit->reject(ValidationException::withMessages(['permisos' => 'La selección contiene acciones que no corresponden a este rol institucional.']));
            }
            $changes = $role->permisos()->sync($ids);
            if ($changes['attached'] || $changes['detached']) {
                app(ActivityRecorder::class)->success(Action::PERMISOS_ROL_ACTUALIZADOS, $actor, [], ['id' => (int) $role->id, 'anadidos' => count($changes['attached']), 'retirados' => count($changes['detached'])]);
            }
        }, $audit);
    }
}
