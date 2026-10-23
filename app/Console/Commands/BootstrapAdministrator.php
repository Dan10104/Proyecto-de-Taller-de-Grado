<?php

namespace App\Console\Commands;

use App\Enums\EstadoUsuario;
use App\Enums\RolInstitucional;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapAdministrator extends Command
{
    protected $signature = 'security:bootstrap-admin {--from-env : Leer credenciales de configuración local, sin imprimirlas}';

    protected $description = 'Crear el primer administrador; nunca reemplaza cuentas existentes.';

    public function handle(): int
    {
        $email = $this->option('from-env') ? config('security.bootstrap_email') : $this->ask('Correo del administrador inicial');
        $password = $this->option('from-env') ? config('security.bootstrap_password') : $this->secret('Contraseña inicial (mínimo 12 caracteres)');
        if (! $this->option('from-env') && $password !== $this->secret('Confirme la contraseña')) {
            $this->error('La confirmación no coincide.');

            return self::FAILURE;
        }
        $email = mb_strtolower(trim((string) $email));
        if (Validator::make(['correo' => $email, 'password' => $password], ['correo' => 'required|email|max:190', 'password' => 'required|string|min:12|max:128'])->fails()) {
            $this->error('Complete credenciales válidas en el canal local seleccionado.');

            return self::FAILURE;
        }
        try {
            DB::transaction(function () use ($email, $password) {
                $role = Rol::where('nombre', RolInstitucional::ADMINISTRADOR->value)->lockForUpdate()->firstOrFail();
                if (Usuario::exists() || array_diff(PermissionCatalog::RECOVERY, $role->permisos()->pluck('codigo')->all())) {
                    throw new \RuntimeException;
                }
                $user = new Usuario(['nombres' => 'Administración', 'apellidos' => 'Local', 'correo' => $email]);
                $user->forceFill(['password_hash' => Hash::make($password), 'estado' => EstadoUsuario::ACTIVO, 'fecha_registro' => now()])->save();
                (new AsignacionRol)->forceFill(['usuario_id' => $user->id, 'rol_id' => $role->id, 'asignado_por_id' => $user->id, 'fecha_asignacion' => now()])->save();
            });
        } catch (\Throwable) {
            $this->error('No se creó el administrador: verifique seeders y ausencia de cuentas previas. No se sobrescribieron credenciales.');

            return self::FAILURE;
        }
        $this->info('Administrador inicial creado. Credenciales no mostradas.');

        return self::SUCCESS;
    }
}
