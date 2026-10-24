<?php

namespace App\Models;

use App\Enums\ResultadoValidacionQr;
use App\Enums\TipoOperacionQr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidacionQr extends Model
{
    use HasFactory;

    protected $table = 'validaciones_qr';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected $hidden = ['hash_leido'];

    protected function casts(): array
    {
        return ['fecha_hora' => 'immutable_datetime', 'resultado' => ResultadoValidacionQr::class, 'tipo_operacion' => TipoOperacionQr::class];
    }

    public function codigoQr(): BelongsTo
    {
        return $this->belongsTo(CodigoQr::class, 'codigo_qr_id');
    }

    public function recursoEsperado(): BelongsTo
    {
        return $this->belongsTo(RecursoInstitucional::class, 'recurso_esperado_id');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(EntregaPrestamo::class, 'entrega_id');
    }

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(DevolucionPrestamo::class, 'devolucion_id');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'validado_por_id');
    }
}
