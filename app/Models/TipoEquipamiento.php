<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEquipamiento extends Model
{
    use HasFactory;

    protected $table = 'tipos_equipamiento';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [];
    }

    public function equipamientos(): HasMany
    {
        return $this->hasMany(EquipamientoTecnologico::class, 'tipo_equipamiento_id');
    }
}
