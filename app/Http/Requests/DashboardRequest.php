<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.ver') ?? false;
    }

    public function rules(): array
    {
        return ['vista' => ['sometimes', Rule::in(['administrativo', 'personal'])], 'periodo' => ['sometimes', Rule::in(['hoy', '7_dias', '30_dias', 'personalizado'])], 'fecha_inicio' => ['sometimes', 'date_format:Y-m-d'], 'fecha_fin' => ['sometimes', 'date_format:Y-m-d']];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (array_diff(array_keys($this->all()), array_keys($this->rules()))) {
                $validator->errors()->add('filtros', 'La consulta contiene filtros no permitidos.');
            }
        }];
    }
}
