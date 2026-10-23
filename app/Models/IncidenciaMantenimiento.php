<?php

namespace App\Models;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidenciaMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'incidencias_mantenimiento';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['tipo' => TipoIncidencia::class, 'estado' => EstadoIncidencia::class, 'inicio' => 'immutable_datetime', 'fin_previsto' => 'immutable_datetime', 'cerrada_en' => 'immutable_datetime'];
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(RecursoInstitucional::class, 'recurso_id');
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'reportado_por_id');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cerrado_por_id');
    }

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(DevolucionPrestamo::class, 'devolucion_id');
    }
}
