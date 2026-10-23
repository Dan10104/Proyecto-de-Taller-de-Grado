<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class OperationalRequest extends FormRequest
{
    public static function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value) === '' ? null : trim($value);
            }
        }

        return $data;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(static::normalize($this->all()));
    }

    public static function protectedRules(array $fields): array
    {
        return array_fill_keys($fields, 'prohibited');
    }

    public static function dateRules(): array
    {
        return ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:1000-01-01T00:00', 'before_or_equal:9999-12-31T23:59'];
    }
}
