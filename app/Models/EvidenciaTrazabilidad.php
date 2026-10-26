<?php

namespace App\Models;

use App\Enums\EstadoEvidencia;
use App\Enums\ResultadoVerificacionEvidencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenciaTrazabilidad extends Model
{
    protected $table = 'evidencias_trazabilidad';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['estado' => EstadoEvidencia::class, 'resultado_verificacion' => ResultadoVerificacionEvidencia::class, 'registrada_localmente_en' => 'immutable_datetime', 'registrada_externamente_en' => 'immutable_datetime', 'ultima_verificacion' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $evidence) {
            if ($evidence->isDirty(['registro_actividad_id', 'algoritmo_hash', 'hash', 'version_canonica', 'registrada_localmente_en'])) {
                throw new \LogicException('La identidad de la evidencia es inmutable.');
            }
            if ($evidence->getRawOriginal('estado') === 'REGISTRADA' && $evidence->isDirty(['estado', 'referencia_externa', 'registrada_externamente_en'])) {
                throw new \LogicException('Un registro confirmado no se sobrescribe.');
            }
        });
        static::deleting(fn () => throw new \LogicException('La evidencia histórica no se elimina.'));
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(RegistroActividad::class, 'registro_actividad_id');
    }
}
