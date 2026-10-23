<?php

namespace App\Models;

use App\Enums\EstadoEspacio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EspacioAcademico extends Model
{
    use HasFactory;

    protected $table = 'espacios_academicos';

    public $timestamps = false;

    protected $fillable = ['piso_id', 'tipo_espacio_id', 'capacidad', 'referencia_ubicacion', 'estado'];

    protected $primaryKey = 'recurso_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['capacidad' => 'integer', 'estado' => EstadoEspacio::class];
    }

    public function recurso()
    {
        return $this->belongsTo(RecursoInstitucional::class, 'recurso_id');
    }

    public function piso()
    {
        return $this->belongsTo(Piso::class);
    }

    public function tipo()
    {
        return $this->belongsTo(TipoEspacio::class, 'tipo_espacio_id');
    }

    public function politica()
    {
        return $this->belongsTo(PoliticaLiberacion::class, 'politica_liberacion_id');
    }

    public function caracteristicas()
    {
        return $this->belongsToMany(CaracteristicaEspacio::class, 'espacios_caracteristicas', 'espacio_id', 'caracteristica_id', 'recurso_id', 'id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'espacio_id', 'recurso_id');
    }

    public function bloqueos()
    {
        return $this->hasMany(BloqueoEspacio::class, 'espacio_id', 'recurso_id');
    }
}
