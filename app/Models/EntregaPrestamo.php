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

    protected static function booted(): void
    {
        static::updating(function (self $delivery) {
            if ($delivery->isDirty(['prestamo_id', 'entregado_por_id', 'receptor_id', 'fecha_hora', 'condicion_entrega', 'observacion'])) {
                throw new \LogicException('La entrega histórica es inmutable.');
            }
        });
        static::deleting(fn () => throw new \LogicException('La entrega histórica no se elimina.'));
    }

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
