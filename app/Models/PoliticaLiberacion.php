<?php

namespace App\Models;

use App\Enums\ModoPoliticaLiberacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoliticaLiberacion extends Model
{
    use HasFactory;

    protected $table = 'politicas_liberacion';

    public $timestamps = false;

    protected $fillable = [];

    protected static function booted(): void
    {
        static::updating(function (self $policy) {
            if (array_diff(array_keys($policy->getDirty()), ['activa'])) {
                throw new \LogicException('Las reglas e identidad de una versión son inmutables.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Las versiones se conservan; desactive la política.'));
    }

    protected function casts(): array
    {
        return ['modo' => ModoPoliticaLiberacion::class, 'version' => 'integer', 'tolerancia_min' => 'integer', 'max_antiguedad_lectura_min' => 'integer', 'requiere_qr' => 'boolean', 'usa_iot' => 'boolean', 'activa' => 'boolean'];
    }

    public function espacios()
    {
        return $this->hasMany(EspacioAcademico::class, 'politica_liberacion_id');
    }

    public function evaluaciones()
    {
        return $this->hasMany(EvaluacionLiberacion::class, 'politica_id');
    }
}
