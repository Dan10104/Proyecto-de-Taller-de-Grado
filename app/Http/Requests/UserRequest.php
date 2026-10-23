<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('usuario') ? 'usuarios.editar' : 'usuarios.crear');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('correo'))) {
            $this->merge(['correo' => mb_strtolower(trim($email))]);
        }
    }

    public function rules(): array
    {
        $editing = $this->route('usuario');

        return [
            'nombres' => ['required', 'string', 'max:120'], 'apellidos' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:190', Rule::unique('usuarios', 'correo')->ignore($editing)],
            'password' => [$editing ? 'nullable' : 'required', 'string', 'max:128', 'confirmed', Password::min(12)],
            'roles' => [$editing || ! $this->user()->can('roles.asignar') ? 'prohibited' : 'sometimes', 'array', 'max:4'],
            'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')],
            'estado' => ['prohibited'], 'password_hash' => ['prohibited'], 'fecha_registro' => ['prohibited'],
        ];
    }
}
