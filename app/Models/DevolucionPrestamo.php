<?php

namespace App\Models;

use App\Enums\CondicionDevolucion;
use App\Enums\EstadoDevolucion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevolucionPrestamo extends Model
{
    use HasFactory;

    protected $table = 'devoluciones_prestamo';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['condicion' => CondicionDevolucion::class, 'estado' => EstadoDevolucion::class, 'registrada_en' => 'immutable_datetime', 'confirmada_en' => 'immutable_datetime'];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recibido_por_id');
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'confirmado_por_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(IncidenciaMantenimiento::class, 'devolucion_id');
    }
}
