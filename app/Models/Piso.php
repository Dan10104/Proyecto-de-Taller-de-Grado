<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Piso extends Model
{
    use HasFactory;

    protected $table = 'pisos';

    public $timestamps = false;

    protected $fillable = ['numero', 'nombre'];

    protected function casts(): array
    {
        return ['numero' => 'integer'];
    }

    public function espacios()
    {
        return $this->hasMany(EspacioAcademico::class, 'piso_id');
    }
}
