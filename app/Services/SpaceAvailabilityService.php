<?php

namespace App\Services;

use App\Enums\EstadoEspacio;
use App\Models\EspacioAcademico;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SpaceAvailabilityService
{
    public function matching(array $filters): Builder
    {
        $query = EspacioAcademico::query()->with(['recurso', 'piso', 'tipo', 'caracteristicas']);
        $q = $filters['q'] ?? '';
        if ($q !== '') {
            $query->whereHas('recurso', fn ($r) => $r->where(fn ($r) => $r->where('codigo', 'like', "%$q%")->orWhere('nombre', 'like', "%$q%")));
        }
        foreach (['piso_id', 'tipo_espacio_id', 'estado'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['capacidad_minima'])) {
            $query->where('capacidad', '>=', $filters['capacidad_minima']);
        }
        // Every requested feature must exist; OR would silently relax requirements.
        foreach ($filters['caracteristicas'] ?? [] as $id) {
            $query->whereHas('caracteristicas', fn ($c) => $c->where('caracteristicas_espacio.id', $id));
        }

        return $query;
    }

    public function candidates(array $filters, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['hora_fin' => 'La hora final debe ser posterior a la hora inicial.']);
        }

        // Phase 4 adds active reservation conflicts here, after its real schema exists.
        return $this->matching($filters)->where('estado', EstadoEspacio::DISPONIBLE->value)
            ->whereDoesntHave('bloqueos', fn ($b) => $b->where('activo', true)
                ->where('inicio', '<', $end->format('Y-m-d H:i:s'))
                ->where('fin', '>', $start->format('Y-m-d H:i:s')));
    }
}
