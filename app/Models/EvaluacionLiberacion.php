<?php

namespace App\Models;

use App\Enums\ResultadoEvaluacionLiberacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionLiberacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_liberacion';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['fecha_hora' => 'immutable_datetime', 'resultado' => ResultadoEvaluacionLiberacion::class];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('La evaluación es histórica e inmutable.'));
        static::deleting(fn () => throw new \LogicException('La evaluación histórica debe conservarse.'));
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function politica()
    {
        return $this->belongsTo(PoliticaLiberacion::class, 'politica_id');
    }

    public function eventos()
    {
        return $this->belongsToMany(EventoOcupacion::class, 'evaluaciones_eventos_ocupacion', 'evaluacion_id', 'evento_id');
    }

    public function validaciones()
    {
        return $this->belongsToMany(ValidacionQr::class, 'evaluaciones_validaciones_qr', 'evaluacion_id', 'validacion_id');
    }
}
