<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaPrestamo extends Model
{
    use HasFactory;

    protected $table = 'entregas_prestamo';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['fecha_hora' => 'immutable_datetime', 'recepcion_confirmada_en' => 'immutable_datetime'];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'entregado_por_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'receptor_id');
    }
}
