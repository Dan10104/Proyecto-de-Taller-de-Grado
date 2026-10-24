<?php

namespace App\Services;

use App\Enums\ModoPoliticaLiberacion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as Validation;

class ReleasePolicyRules
{
    public function validator(array $data): Validation
    {
        return Validator::make($data, [
            'nombre' => ['required', 'string', 'max:120'],
            'version' => ['required', 'integer', 'between:1,2147483647'],
            'tolerancia_min' => ['required', 'integer', 'between:0,2147483647'],
            'requiere_qr' => ['required', 'boolean'], 'usa_iot' => ['required', 'boolean'], 'activa' => ['required', 'boolean'],
            'max_antiguedad_lectura_min' => ['nullable', 'integer', 'between:0,2147483647'],
            'modo' => ['required', Rule::enum(ModoPoliticaLiberacion::class)],
        ])->after(function (Validation $v) use ($data) {
            if (! ($data['requiere_qr'] ?? false) && ! ($data['usa_iot'] ?? false)) {
                $v->errors()->add('requiere_qr', 'Seleccione al menos una fuente obligatoria.');
            }
            if (($data['usa_iot'] ?? false) && ! isset($data['max_antiguedad_lectura_min'])) {
                $v->errors()->add('max_antiguedad_lectura_min', 'Indique la antigüedad máxima de lectura IoT.');
            }
            if (! ($data['usa_iot'] ?? false) && isset($data['max_antiguedad_lectura_min'])) {
                $v->errors()->add('max_antiguedad_lectura_min', 'Sin IoT obligatorio, la antigüedad debe estar vacía.');
            }
            if (is_string($data['nombre'] ?? null) && trim($data['nombre']) === '') {
                $v->errors()->add('nombre', 'Indique un nombre.');
            }
        });
    }

    public function valid(object $policy): bool
    {
        return $this->validator((array) $policy)->passes();
    }
}
