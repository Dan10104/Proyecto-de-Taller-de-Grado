<?php

namespace App\Http\Requests;

class EquipmentTypeRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipamientos.gestionar');
    }

    public function rules(): array
    {
        return ['codigo' => 'required|string|max:60', 'nombre' => 'required|string|max:120', ...self::protectedRules(['id'])];
    }
}
