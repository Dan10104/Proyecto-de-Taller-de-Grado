<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecursoInstitucional extends Model
{
    use HasFactory;

    protected $table = 'recursos_institucionales';

    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre'];

    public function espacio()
    {
        return $this->hasOne(EspacioAcademico::class, 'recurso_id');
    }
}
