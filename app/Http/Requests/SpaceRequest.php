<?php

namespace App\Http\Requests;

use App\Enums\EstadoEspacio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('espacios.gestionar');
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:60', Rule::unique('recursos_institucionales', 'codigo')->ignore($this->route('espacio')?->getKey())],
            'nombre' => ['required', 'string', 'max:160'],
            'piso_id' => ['required', 'integer', Rule::exists('pisos', 'id')],
            'tipo_espacio_id' => ['required', 'integer', Rule::exists('tipos_espacio', 'id')],
            'capacidad' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'referencia_ubicacion' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'required', Rule::enum(EstadoEspacio::class)],
            'caracteristicas' => ['present', 'array', 'max:100'],
            'caracteristicas.*' => ['required', 'integer', 'distinct', Rule::exists('caracteristicas_espacio', 'id')],
            'politica_liberacion_id' => ['prohibited'], 'recurso_id' => ['prohibited'], 'id' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['codigo' => 'código', 'piso_id' => 'piso', 'tipo_espacio_id' => 'tipo de espacio', 'caracteristicas' => 'características'];
    }
}
