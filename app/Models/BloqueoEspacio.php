<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloqueoEspacio extends Model
{
    use HasFactory;

    protected $table = 'bloqueos_espacio';

    public $timestamps = false;

    protected $fillable = [];

    protected function casts(): array
    {
        return ['inicio' => 'immutable_datetime', 'fin' => 'immutable_datetime', 'activo' => 'boolean'];
    }

    public function espacio()
    {
        return $this->belongsTo(EspacioAcademico::class, 'espacio_id', 'recurso_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }
}
