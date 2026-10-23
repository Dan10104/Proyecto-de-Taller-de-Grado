<?php

namespace App\Http\Requests;

use App\Enums\EstadoEquipamiento;
use Illuminate\Validation\Rule;

class EquipmentStateRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipamientos.cambiar_estado');
    }

    public function rules(): array
    {
        return ['estado' => ['required', Rule::enum(EstadoEquipamiento::class)], 'apto' => 'required_if:estado,DISPONIBLE|accepted_if:estado,DISPONIBLE|boolean'];
    }
}
