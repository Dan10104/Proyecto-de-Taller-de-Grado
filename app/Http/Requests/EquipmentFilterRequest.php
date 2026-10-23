<?php

namespace App\Http\Requests;

use App\Enums\EstadoEquipamiento;
use Illuminate\Validation\Rule;

class EquipmentFilterRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipamientos.ver');
    }

    public function rules(): array
    {
        return ['q' => 'nullable|string|max:160', 'tipo_equipamiento_id' => 'nullable|integer|exists:tipos_equipamiento,id', 'espacio_base_id' => 'nullable|integer|exists:espacios_academicos,recurso_id', 'piso_id' => 'nullable|integer|exists:pisos,id',
            'marca' => 'nullable|string|max:120', 'modelo' => 'nullable|string|max:120', 'estado' => ['nullable', Rule::enum(EstadoEquipamiento::class)],
            'inicio_previsto' => ['nullable', 'required_with:fin_previsto', 'date_format:Y-m-d\TH:i', 'after_or_equal:1000-01-01T00:00', 'before_or_equal:9999-12-31T23:59'],
            'fin_previsto' => ['nullable', 'required_with:inicio_previsto', 'date_format:Y-m-d\TH:i', 'after:inicio_previsto', 'before_or_equal:9999-12-31T23:59'], 'page' => 'nullable|integer|min:1'];
    }
}
