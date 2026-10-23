<?php

namespace App\Http\Requests;

use App\Models\Reserva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Reserva::class);
    }

    public static function protectedRules(): array
    {
        return array_fill_keys(['id', 'codigo', 'solicitante_id', 'estado', 'solicitada_en', 'resuelta_por_id', 'resuelta_en', 'motivo_rechazo', 'cancelada_por_id', 'cancelada_en', 'motivo_cancelacion'], ['prohibited']);
    }

    public static function inputRules(): array
    {
        return ['espacio_id' => ['required', 'integer', Rule::exists('espacios_academicos', 'recurso_id')],
            'inicio' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31T23:59'],
            'fin' => ['required', 'date_format:Y-m-d\TH:i', 'after:inicio', 'before_or_equal:9999-12-31T23:59'],
            'proposito' => ['required', 'string', 'max:500'], ...self::protectedRules()];
    }

    public function rules(): array
    {
        return self::inputRules();
    }
}
