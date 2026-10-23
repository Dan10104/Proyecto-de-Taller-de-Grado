<?php

namespace Database\Seeders;

use App\Enums\RolInstitucional;
use App\Models\Permiso;
use App\Models\Rol;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecuritySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $newPermissions = [];
            foreach (PermissionCatalog::DEFINITIONS as $code => $definition) {
                $permission = Permiso::updateOrCreate(['codigo' => $code], ['nombre' => $definition['nombre'], 'descripcion' => $definition['fuente']]);
                if ($permission->wasRecentlyCreated) {
                    $newPermissions[] = $permission->id;
                }
            }
            foreach (RolInstitucional::cases() as $base) {
                $role = Rol::firstOrCreate(['nombre' => $base->value], ['descripcion' => 'Rol institucional de la FICCT.']);
                $eligible = Permiso::whereIn('codigo', PermissionCatalog::forRole($base->value));
                if (! $role->wasRecentlyCreated) {
                    $eligible->whereIn('id', $newPermissions);
                }
                $role->permisos()->syncWithoutDetaching($eligible->pluck('id'));
            }
        });
    }
}
