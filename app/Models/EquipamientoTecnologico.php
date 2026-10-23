<?php

namespace App\Models;

use App\Enums\EstadoEquipamiento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipamientoTecnologico extends Model
{
    use HasFactory;

    protected $table = 'equipamientos_tecnologicos';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected $primaryKey = 'recurso_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['estado' => EstadoEquipamiento::class];
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(RecursoInstitucional::class, 'recurso_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipamiento::class, 'tipo_equipamiento_id');
    }

    public function espacioBase(): BelongsTo
    {
        return $this->belongsTo(EspacioAcademico::class, 'espacio_base_id', 'recurso_id');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class, 'equipamiento_id', 'recurso_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(IncidenciaMantenimiento::class, 'recurso_id', 'recurso_id');
    }
}
