<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpaceBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('block', $this->route('espacio'));
    }

    public function rules(): array
    {
        return ['inicio' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31T23:59'], 'fin' => ['required', 'date_format:Y-m-d\TH:i', 'after:inicio', 'before_or_equal:9999-12-31T23:59'], 'motivo' => ['required', 'string', 'max:500'], 'responsable_id' => ['prohibited'], 'espacio_id' => ['prohibited'], 'activo' => ['prohibited']];
    }
}
