<?php

namespace App\Models;

use App\Enums\TipoEventoOcupacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoOcupacion extends Model
{
    use HasFactory;

    protected $table = 'eventos_ocupacion';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['tipo' => TipoEventoOcupacion::class, 'valor' => 'decimal:2', 'fecha_evento' => 'immutable_datetime', 'fecha_recepcion' => 'immutable_datetime'];
    }

    public function sensor()
    {
        return $this->belongsTo(SensorIot::class, 'sensor_id');
    }

    public function espacioHistorico()
    {
        return $this->belongsTo(EspacioAcademico::class, 'espacio_historico_id', 'recurso_id');
    }
}
