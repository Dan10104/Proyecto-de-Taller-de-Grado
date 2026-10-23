<?php

namespace App\Models;

use App\Enums\EstadoReserva;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['estado' => EstadoReserva::class, 'inicio' => 'immutable_datetime', 'fin' => 'immutable_datetime', 'solicitada_en' => 'immutable_datetime', 'resuelta_en' => 'immutable_datetime', 'cancelada_en' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Reserva $reserva) {
            $reserva->codigo ??= 'RES-'.strtolower((string) Str::uuid());
        });
        static::updating(function (Reserva $reserva) {
            if ($reserva->isDirty('codigo')) {
                throw new LogicException('El código de reserva es inmutable.');
            }
        });
    }

    public function espacio()
    {
        return $this->belongsTo(EspacioAcademico::class, 'espacio_id', 'recurso_id');
    }

    public function solicitante()
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    public function resueltaPor()
    {
        return $this->belongsTo(Usuario::class, 'resuelta_por_id');
    }

    public function canceladaPor()
    {
        return $this->belongsTo(Usuario::class, 'cancelada_por_id');
    }
}
