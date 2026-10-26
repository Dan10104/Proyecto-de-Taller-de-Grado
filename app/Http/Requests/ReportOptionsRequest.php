<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reportes.ver') ?? false;
    }

    public function rules(): array
    {
        return ['reporte' => ['required', 'string', 'max:40'], 'campo' => ['required', 'string', 'max:40'], 'buscar' => ['sometimes', 'nullable', 'string', 'max:80'], 'seleccionado' => ['sometimes', 'integer', 'min:1']];
    }

    public function after(): array
    {
        return [function ($v) {
            if (array_diff(array_keys($this->all()), array_keys($this->rules()))) {
                $v->errors()->add('campo', 'Parámetro no permitido.');
            }
        }];
    }
}
