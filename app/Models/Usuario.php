<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    use HasFactory;

    protected $table = 'usuarios';

    public $timestamps = false;

    protected $fillable = ['nombres', 'apellidos', 'correo'];

    protected $hidden = ['password_hash'];

    protected $authPasswordName = 'password_hash';

    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return ['estado' => EstadoUsuario::class, 'fecha_registro' => 'immutable_datetime'];
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionRol::class, 'usuario_id');
    }

    public function rolesActivos(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'asignaciones_roles', 'usuario_id', 'rol_id')->wherePivotNull('fecha_retiro')->distinct();
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionAcceso::class, 'usuario_id');
    }
}
