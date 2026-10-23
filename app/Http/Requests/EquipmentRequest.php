<?php

namespace App\Http\Requests;

use App\Enums\EstadoEquipamiento;
use Illuminate\Validation\Rule;

class EquipmentRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipamientos.gestionar');
    }

    public function rules(): array
    {
        return self::inputRules($this->route('equipamiento')?->getKey());
    }

    public static function inputRules(?int $id = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:60', Rule::unique('recursos_institucionales', 'codigo')->ignore($id)],
            'nombre' => 'required|string|max:160',
            'tipo_equipamiento_id' => 'required|integer|exists:tipos_equipamiento,id',
            'espacio_base_id' => 'nullable|integer|exists:espacios_academicos,recurso_id',
            'descripcion' => 'nullable|string|max:16000', 'marca' => 'nullable|string|max:120', 'modelo' => 'nullable|string|max:120',
            'numero_serie' => ['nullable', 'string', 'max:160', Rule::unique('equipamientos_tecnologicos', 'numero_serie')->ignore($id, 'recurso_id')],
            'referencia_ubicacion' => 'nullable|string|max:255', 'estado' => ['sometimes', 'required', Rule::enum(EstadoEquipamiento::class)],
            'apto' => 'sometimes|boolean',
            ...self::protectedRules(['id', 'recurso_id', 'created_at', 'updated_at', 'deleted_at']),
        ];
    }
}
