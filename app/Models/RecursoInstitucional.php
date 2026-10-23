<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecursoInstitucional extends Model
{
    use HasFactory;

    protected $table = 'recursos_institucionales';

    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre'];

    public function espacio()
    {
        return $this->hasOne(EspacioAcademico::class, 'recurso_id');
    }

    public function equipamiento(): HasOne
    {
        return $this->hasOne(EquipamientoTecnologico::class, 'recurso_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(IncidenciaMantenimiento::class, 'recurso_id');
    }
}
