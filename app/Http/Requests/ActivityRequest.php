<?php

namespace App\Http\Requests;

use App\Services\Activity\ActivityFilters;
use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('actividad.ver') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function filters(): ActivityFilters
    {
        return ActivityFilters::from($this->query());
    }
}
