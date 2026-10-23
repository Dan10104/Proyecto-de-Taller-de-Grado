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
            foreach (PermissionCatalog::DEFINITIONS as $code => $definition) {
                Permiso::updateOrCreate(['codigo' => $code], ['nombre' => $definition['nombre'], 'descripcion' => $definition['fuente']]);
            }
            foreach (RolInstitucional::cases() as $base) {
                $role = Rol::firstOrCreate(['nombre' => $base->value], ['descripcion' => 'Rol institucional de la FICCT.']);
                if ($base === RolInstitucional::ADMINISTRADOR && $role->wasRecentlyCreated) {
                    $role->permisos()->sync(Permiso::whereIn('codigo', array_keys(PermissionCatalog::DEFINITIONS))->pluck('id'));
                }
            }
        });
    }
}
