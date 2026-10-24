<?php

namespace App\Models;

use App\Enums\EstadoSensorIot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorIot extends Model
{
    use HasFactory;

    protected $table = 'sensores_iot';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['estado' => EstadoSensorIot::class, 'ultima_comunicacion' => 'immutable_datetime'];
    }

    public function espacioActual()
    {
        return $this->belongsTo(EspacioAcademico::class, 'espacio_actual_id', 'recurso_id');
    }

    public function eventos()
    {
        return $this->hasMany(EventoOcupacion::class, 'sensor_id');
    }
}
