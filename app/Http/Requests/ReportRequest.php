<?php

namespace App\Http\Requests;

use App\Services\Reporting\ReportRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reportes.ver') ?? false;
    }

    public function rules(): array
    {
        $export = $this->isMethod('post');

        return ['reporte' => [$export ? 'required' : 'sometimes', 'string', Rule::in(array_keys(app(ReportRegistry::class)->definitions()))], 'filtros' => ['sometimes', 'array'],
            'formato' => [$export ? 'required' : 'prohibited', Rule::in(['pdf', 'xlsx'])], 'pagina' => [$export ? 'prohibited' : 'sometimes', 'integer', 'min:1', 'max:1000000'],
            'por_pagina' => [$export ? 'prohibited' : 'sometimes', 'integer', Rule::in([25, 50, 100])]];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (array_diff(array_keys($this->all()), [...array_keys($this->rules()), '_token'])) {
                $validator->errors()->add('filtros', 'La consulta contiene parámetros no permitidos.');
            }
        }];
    }
}
