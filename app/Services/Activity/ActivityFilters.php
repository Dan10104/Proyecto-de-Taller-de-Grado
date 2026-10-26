<?php

namespace App\Services\Activity;

use App\Enums\ActivityAction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class ActivityFilters
{
    public const ENTITIES = ['USUARIO', 'RECURSO', 'ESPACIO', 'EQUIPAMIENTO', 'RESERVA', 'PRESTAMO', 'INCIDENCIA', 'SIN_REFERENCIA'];

    private function __construct(public array $values, public CarbonImmutable $start, public CarbonImmutable $end) {}

    public static function from(array $input): self
    {
        $rules = [
            'fecha_desde' => ['nullable', 'required_with:fecha_hasta', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-30'],
            'fecha_hasta' => ['nullable', 'required_with:fecha_desde', 'date_format:Y-m-d', 'after_or_equal:fecha_desde', 'before_or_equal:9999-12-30'],
            'accion' => ['nullable', Rule::enum(ActivityAction::class)],
            'resultado' => ['nullable', Rule::in(['EXITO', 'RECHAZO', 'ERROR'])],
            'critica' => ['nullable', 'boolean'],
            'tipo_entidad' => ['nullable', Rule::in(self::ENTITIES)],
            'responsable_tipo' => ['nullable', Rule::in(['todos', 'humano', 'proceso'])],
            'proceso_responsable' => ['nullable', Rule::in(ActivityRecorder::PROCESSES)],
            'page' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
        ];
        foreach (['actor_id', ...ActivityRecorder::CONTEXT] as $key) {
            $rules[$key] = ['nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX];
            if (isset($input[$key]) && is_bool($input[$key])) {
                throw ValidationException::withMessages([$key => 'Indique un identificador entero positivo.']);
            }
        }
        if (array_diff(array_keys($input), array_keys($rules))) {
            throw ValidationException::withMessages(['filtros' => 'La consulta contiene parámetros no permitidos.']);
        }
        $input = array_filter($input, fn ($v) => $v !== '' && $v !== null);
        $values = Validator::make($input, $rules)->validate();
        $today = CarbonImmutable::now('America/La_Paz')->startOfDay();
        $values['fecha_desde'] ??= $today->subDays(29)->toDateString();
        $values['fecha_hasta'] ??= $today->toDateString();

        return new self($values, CarbonImmutable::parse($values['fecha_desde'], 'America/La_Paz')->startOfDay(), CarbonImmutable::parse($values['fecha_hasta'], 'America/La_Paz')->addDay()->startOfDay());
    }
}
