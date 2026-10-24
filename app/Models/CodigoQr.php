<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodigoQr extends Model
{
    use HasFactory;

    protected $table = 'codigos_qr';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'generada_en' => 'immutable_datetime', 'vence_en' => 'immutable_datetime'];
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(RecursoInstitucional::class, 'recurso_id');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function validaciones(): HasMany
    {
        return $this->hasMany(ValidacionQr::class, 'codigo_qr_id');
    }
}
