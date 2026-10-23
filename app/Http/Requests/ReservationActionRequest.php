<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->routeIs('reservas.cancel') ? 'cancel' : 'resolve', $this->route('reserva'));
    }

    public function rules(): array
    {
        $rules = [...ReservationRequest::protectedRules(), ...array_fill_keys(['espacio_id', 'inicio', 'fin', 'proposito'], ['prohibited'])];
        if ($this->routeIs('reservas.reject')) {
            $rules['motivo_rechazo'] = ['nullable', 'string', 'max:500'];
        }
        if ($this->routeIs('reservas.cancel')) {
            $rules['motivo_cancelacion'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }
}
