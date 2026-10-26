<?php

namespace App\Models;

use App\Enums\ResultadoRegistroActividad;
use App\Services\Notifications\NotificationDispatch;
use App\Services\Notifications\NotificationRuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RegistroActividad extends Model
{
    protected $table = 'registros_actividad';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['fecha_hora' => 'immutable_datetime', 'resultado' => ResultadoRegistroActividad::class, 'critica' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::created(function (self $activity) {
            if ($activity->resultado === ResultadoRegistroActividad::EXITO && isset(NotificationRuleRegistry::RULES[$activity->accion])) {
                NotificationDispatch::afterCommit((int) $activity->id);
            }
        });
        static::updating(fn () => throw new \LogicException('La actividad histórica es inmutable.'));
        static::deleting(fn () => throw new \LogicException('La actividad histórica no se elimina.'));
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'registro_actividad_id');
    }

    public function evidencia(): HasOne
    {
        return $this->hasOne(EvidenciaTrazabilidad::class, 'registro_actividad_id');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'actor_id');
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(RecursoInstitucional::class, 'recurso_id');
    }

    public function usuarioAfectado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_afectado_id');
    }
}
