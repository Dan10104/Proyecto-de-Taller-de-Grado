<?php

namespace App\Models;

use App\Enums\TipoNotificacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['tipo' => TipoNotificacion::class, 'creada_en' => 'immutable_datetime', 'leida_en' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $notification) {
            if (array_diff(array_keys($notification->getDirty()), ['leida_en']) || ($notification->isDirty('leida_en') && ($notification->getRawOriginal('leida_en') !== null || $notification->leida_en === null))) {
                throw new \LogicException('El aviso histórico y la primera lectura se conservan.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Las notificaciones históricas no se eliminan.'));
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'destinatario_id');
    }

    public function registroActividad(): BelongsTo
    {
        return $this->belongsTo(RegistroActividad::class, 'registro_actividad_id');
    }
}
