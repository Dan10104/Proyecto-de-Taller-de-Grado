<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('correo'))) {
            $this->merge(['correo' => mb_strtolower(trim($email))]);
        }
    }

    public function rules(): array
    {
        return ['correo' => ['required', 'string', 'email', 'max:190'], 'password' => ['required', 'string', 'max:128']];
    }
}
