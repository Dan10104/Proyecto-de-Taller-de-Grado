<?php

namespace App\Services;

use App\Enums\EstadoEspacio;
use App\Enums\EstadoReserva;
use App\Exceptions\ReservationConflictException;
use App\Models\EspacioAcademico;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

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
        $this->validatePeriod($start, $end);

        return $this->operational($filters)
            ->whereDoesntHave('bloqueos', fn ($q) => $this->activeBlocks($q, $start, $end))
            ->whereDoesntHave('reservas', fn ($q) => $this->approvedReservations($q, $start, $end))
            ->whereDoesntHave('incidencias', fn ($q) => $q->where('estado', 'ABIERTA'));
    }

    public function assertAvailable(EspacioAcademico $space, CarbonImmutable $start, CarbonImmutable $end, ?int $exceptReservation = null, array $filters = []): void
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('La revalidación requiere una transacción y coordinación del espacio.');
        }
        $this->validatePeriod($start, $end);
        // Current reads are essential after waiting: an outer FOR UPDATE does not lock NOT EXISTS subqueries.
        $operational = $this->operational($filters)->whereKey($space->getKey())->lockForUpdate()->first();
        $block = $this->activeBlocks($space->bloqueos()->getQuery(), $start, $end)->lockForUpdate()->first(['id']);
        $reservation = $this->approvedReservations($space->reservas()->getQuery(), $start, $end, $exceptReservation)->lockForUpdate()->first(['id']);
        $incident = $space->incidencias()->where('estado', 'ABIERTA')->orderBy('id')->lockForUpdate()->first(['id']);
        if (! $operational || $block || $reservation || $incident) {
            throw new ReservationConflictException('El espacio ya no se encuentra disponible para el periodo solicitado.');
        }
    }

    private function operational(array $filters): Builder
    {
        return $this->matching($filters)->where('estado', EstadoEspacio::DISPONIBLE->value);
    }

    private function activeBlocks(Builder $query, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $this->overlapping($query->where('activo', true), $start, $end);
    }

    private function approvedReservations(Builder $query, CarbonImmutable $start, CarbonImmutable $end, ?int $except = null): Builder
    {
        return $this->overlapping($query->where('estado', EstadoReserva::APROBADA->value), $start, $end)
            ->when($except !== null, fn ($q) => $q->where('id', '<>', $except));
    }

    private function overlapping(Builder $query, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $query->where('inicio', '<', $end->format('Y-m-d H:i:s'))->where('fin', '>', $start->format('Y-m-d H:i:s'));
    }

    private function validatePeriod(CarbonImmutable $start, CarbonImmutable $end): void
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['hora_fin' => 'La hora final debe ser posterior a la hora inicial.']);
        }
    }
}
