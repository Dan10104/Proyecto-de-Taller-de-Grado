<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RolPermiso extends Pivot
{
    protected $table = 'roles_permisos';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['rol_id', 'permiso_id'];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'permiso_id');
    }
}
