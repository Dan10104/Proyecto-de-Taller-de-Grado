<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('roles.gestionar_permisos');
    }

    public function rules(): array
    {
        return ['permisos' => ['present', 'array', 'max:100'], 'permisos.*' => ['integer', 'distinct', Rule::exists('permisos', 'id')]];
    }
}
