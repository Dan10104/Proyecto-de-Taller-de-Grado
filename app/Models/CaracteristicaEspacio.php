<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaracteristicaEspacio extends Model
{
    use HasFactory;

    protected $table = 'caracteristicas_espacio';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    public function espacios()
    {
        return $this->belongsToMany(EspacioAcademico::class, 'espacios_caracteristicas', 'caracteristica_id', 'espacio_id', 'id', 'recurso_id');
    }
}
