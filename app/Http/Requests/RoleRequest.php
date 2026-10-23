<?php

namespace App\Http\Requests;

use App\Enums\RolInstitucional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('rol') ? 'roles.editar' : 'roles.crear');
    }

    public function rules(): array
    {
        return ['nombre' => ['required', 'string', 'max:100', Rule::in(RolInstitucional::nombres()), Rule::unique('roles', 'nombre')->ignore($this->route('rol'))], 'descripcion' => ['nullable', 'string', 'max:255'], 'permisos' => ['prohibited']];
    }
}
