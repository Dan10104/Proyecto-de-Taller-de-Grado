<?php

namespace App\Http\Requests;

use App\Enums\EstadoReserva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationFilterRequest extends FormRequest
{
    public function administrative(): bool
    {
        return $this->input('alcance', $this->user()->can('reservas.ver_administrativas') ? 'administrativas' : 'propias') === 'administrativas';
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->administrative() ? 'reservas.ver_administrativas' : 'reservas.ver_propias');
    }

    public function rules(): array
    {
        return ['alcance' => ['nullable', Rule::in(['propias', 'administrativas'])], 'fecha' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'estado' => ['nullable', Rule::enum(EstadoReserva::class)], 'espacio_id' => ['nullable', 'integer', 'min:1'],
            'solicitante_id' => $this->administrative() ? ['nullable', 'integer', 'min:1'] : ['prohibited'], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
