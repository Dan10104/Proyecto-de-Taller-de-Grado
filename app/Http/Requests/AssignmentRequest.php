<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('delete') ? 'roles.retirar' : 'roles.asignar');
    }

    public function rules(): array
    {
        return ['usuario_id' => ['required', 'integer', Rule::exists('usuarios', 'id')]];
    }
}
