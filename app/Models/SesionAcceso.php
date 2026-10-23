<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionAcceso extends Model
{
    protected $table = 'sesiones_acceso';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['creada_en' => 'immutable_datetime', 'expira_en' => 'immutable_datetime', 'revocada_en' => 'immutable_datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
