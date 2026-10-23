<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEspacio extends Model
{
    use HasFactory;

    protected $table = 'tipos_espacio';

    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre'];

    public function espacios()
    {
        return $this->hasMany(EspacioAcademico::class, 'tipo_espacio_id');
    }
}
