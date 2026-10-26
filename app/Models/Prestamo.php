<?php

namespace App\Models;

use App\Enums\EstadoPrestamo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Prestamo extends Model
{
    use HasFactory;

    protected $table = 'prestamos';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['estado' => EstadoPrestamo::class, 'inicio_previsto' => 'immutable_datetime', 'fin_previsto' => 'immutable_datetime', 'solicitado_en' => 'immutable_datetime', 'resuelto_en' => 'immutable_datetime'];
    }

    public function equipamiento(): BelongsTo
    {
        return $this->belongsTo(EquipamientoTecnologico::class, 'equipamiento_id', 'recurso_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'resuelto_por_id');
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(EntregaPrestamo::class, 'prestamo_id');
    }

    public function devolucion(): HasOne
    {
        return $this->hasOne(DevolucionPrestamo::class, 'prestamo_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $loan) {
            $loan->codigo ??= 'PRE-'.strtolower((string) Str::uuid());
        });
        static::updating(function (self $loan) {
            if ($loan->isDirty(['equipamiento_id', 'solicitante_id']) && $loan->entrega()->exists()) {
                throw new \LogicException('El contexto de un préstamo entregado es inmutable.');
            }
            if ($loan->isDirty('codigo')) {
                throw new \LogicException('El código del préstamo es inmutable.');
            }
        });
    }

    public function vencido(): bool
    {
        return $this->estado === EstadoPrestamo::ENTREGADO && now()->greaterThan($this->fin_previsto);
    }
}
