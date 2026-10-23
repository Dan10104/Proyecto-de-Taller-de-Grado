<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')->using(RolPermiso::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionRol::class, 'rol_id');
    }

    public function usuariosActivos(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'asignaciones_roles', 'rol_id', 'usuario_id')->wherePivotNull('fecha_retiro')->distinct();
    }
}
