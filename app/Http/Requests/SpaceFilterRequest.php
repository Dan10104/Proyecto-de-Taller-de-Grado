<?php

namespace App\Http\Requests;

use App\Enums\EstadoEspacio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpaceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('espacios.ver');
    }

    public function rules(): array
    {
        $rules = ['q' => ['nullable', 'string', 'max:160'], 'piso_id' => ['nullable', 'integer', Rule::exists('pisos', 'id')], 'tipo_espacio_id' => ['nullable', 'integer', Rule::exists('tipos_espacio', 'id')], 'capacidad_minima' => ['nullable', 'integer', 'min:1', 'max:2147483647'], 'estado' => ['nullable', Rule::enum(EstadoEspacio::class)], 'caracteristicas' => ['nullable', 'array', 'max:100'], 'caracteristicas.*' => ['integer', 'distinct', Rule::exists('caracteristicas_espacio', 'id')], 'page' => ['nullable', 'integer', 'min:1']];
        if ($this->routeIs('disponibilidad.index')) {
            $rules += ['buscar' => ['nullable', Rule::in(['1'])], 'fecha' => ['required_with:buscar,hora_inicio,hora_fin', 'nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'], 'hora_inicio' => ['required_with:buscar,fecha,hora_fin', 'nullable', 'date_format:H:i'], 'hora_fin' => ['required_with:buscar,fecha,hora_inicio', 'nullable', 'date_format:H:i', 'after:hora_inicio']];
        }

        return $rules;
    }
}
