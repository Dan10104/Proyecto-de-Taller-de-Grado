<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoliticaLiberacion extends Model
{
    use HasFactory;

    protected $table = 'politicas_liberacion';

    public $timestamps = false;

    protected $fillable = [];

    // Structural FK dependency only; functional policies belong to Phase 8.
    protected function casts(): array
    {
        return ['version' => 'integer', 'tolerancia_min' => 'integer', 'max_antiguedad_lectura_min' => 'integer', 'requiere_qr' => 'boolean', 'usa_iot' => 'boolean', 'activa' => 'boolean'];
    }

    public function espacios()
    {
        return $this->hasMany(EspacioAcademico::class, 'politica_liberacion_id');
    }
}
