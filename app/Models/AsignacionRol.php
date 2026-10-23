<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionRol extends Model
{
    protected $table = 'asignaciones_roles';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['fecha_asignacion' => 'immutable_datetime', 'fecha_retiro' => 'immutable_datetime'];
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereNull('fecha_retiro');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function asignador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'asignado_por_id');
    }

    public function retirador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'retirado_por_id');
    }
}
