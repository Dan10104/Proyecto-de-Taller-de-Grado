<?php

namespace App\Services\Reporting;

use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportFilters
{
    public function normalize(ReportDefinition $definition, Usuario $actor, array $input, CarbonImmutable $now): array
    {
        $allowed = array_keys($definition->filters);
        if ($definition->dated) {
            $allowed = [...$allowed, 'periodo', 'fecha_inicio', 'fecha_fin'];
        }
        if (array_diff(array_keys($input), $allowed)) {
            throw ValidationException::withMessages(['filtros' => 'El reporte contiene filtros no permitidos.']);
        }
        $input = array_filter($input, fn ($v) => $v !== null && $v !== '');
        $rules = [];
        foreach ($definition->filters as $key => $spec) {
            $rules[$key] = is_array($spec) ? ['sometimes', Rule::in($spec)] : ($spec === 'reference' ? ['sometimes', 'integer', 'min:1', 'max:9223372036854775807'] : ['sometimes', 'date_format:Y-m-d\\TH:i', 'after_or_equal:1000-01-01', 'before:9999-12-31']);
        }
        Validator::make($input, $rules)->validate();
        if ($definition->dated) {
            $period = ReportPeriod::from($input, $now);
            $input['periodo'] = $period->preset;
            if ($period->preset === 'personalizado') {
                $input += $period->filters();
            }
        }
        if ($definition->id === 'actividad-operacional') {
            $families = ActivityReportQuery::allowed(ReportingAccess::for($actor));
            Validator::make($input, ['categoria' => ['sometimes', Rule::in(array_keys($families))], 'accion' => ['sometimes', Rule::in(array_merge(...array_column($families, 'actions')))]])->validate();
        }
        if ($definition->id === 'espacios') {
            $input['vista'] ??= 'inventario';
            if ($input['vista'] === 'disponibilidad') {
                Validator::make($input, ['inicio' => ['required'], 'fin' => ['required', 'after:inicio']])->validate();
            } elseif (array_intersect(array_keys($input), ['inicio', 'fin', 'disponible'])) {
                throw ValidationException::withMessages(['vista' => 'Seleccione Disponibilidad para consultar un intervalo.']);
            }
        }
        if (isset($input['sin_ubicacion'], $input['espacio_base_id'])) {
            throw ValidationException::withMessages(['espacio_base_id' => 'Elija una ubicación o Sin ubicación base.']);
        }
        foreach ($definition->filters as $key => $spec) {
            if ($spec === 'reference' && isset($input[$key])) {
                $input[$key] = (int) $input[$key];
                if (! app(ReportReferenceService::class)->query($definition, $actor, $key)->where('option_id', $input[$key])->exists()) {
                    throw ValidationException::withMessages([$key => 'La opción seleccionada no está disponible para esta consulta.']);
                }
            }
        }
        ksort($input);

        return $input;
    }
}
