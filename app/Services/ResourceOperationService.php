<?php

namespace App\Services;

use App\Exceptions\EquipmentConflictException;
use App\Models\DevolucionPrestamo;
use App\Models\EntregaPrestamo;
use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\IncidenciaMantenimiento;
use App\Models\Prestamo;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

class ResourceOperationService
{
    public function transaction(Usuario $actor, string $permission, callable $operation): mixed
    {
        try {
            return DB::transaction(function () use ($actor, $permission, $operation) {
                Rol::where('nombre', 'Administrador')->lockForUpdate()->firstOrFail();
                $fresh = $actor->fresh();
                Gate::forUser($fresh)->authorize($permission);

                return $operation($fresh);
            }, 3);
        } catch (QueryException $e) {
            if (in_array($e->errorInfo[1] ?? null, [1205, 1213], true)) {
                throw new EquipmentConflictException('Otra operación está modificando el recurso. Actualice la página e inténtelo nuevamente.');
            }
            throw $e;
        }
    }

    // All dependent current reads precede business checks. Never acquire an earlier lock after incidences.
    public function lock(int $resourceId, bool $equipment, ?int $loanId = null, ?int $incidentId = null): array
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('La coordinación requiere una transacción.');
        }
        $resource = ($equipment ? EquipamientoTecnologico::query() : EspacioAcademico::query())->whereKey($resourceId)->lockForUpdate()->firstOrFail();

        return $this->related($resource, $loanId, $incidentId);
    }

    public function related(Model $resource, ?int $loanId = null, ?int $incidentId = null): array
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('La coordinación requiere una transacción.');
        }
        $equipment = $resource instanceof EquipamientoTecnologico;
        $loan = $loanId ? Prestamo::whereKey($loanId)->lockForUpdate()->firstOrFail() : null;
        if ($loan && (! $equipment || $loan->equipamiento_id !== $resource->getKey())) {
            throw new EquipmentConflictException('El préstamo ya no corresponde al recurso seleccionado.');
        }
        $blocks = $equipment ? collect() : $resource->bloqueos()->where('activo', true)->orderBy('id')->lockForUpdate()->get();
        $reservations = $equipment ? collect() : $resource->reservas()->whereIn('estado', ['PENDIENTE', 'APROBADA'])->where('fin', '>', now())->orderBy('id')->lockForUpdate()->get();
        $loans = $equipment ? $resource->prestamos()->where(fn ($q) => $q->whereIn('estado', ['APROBADO', 'ENTREGADO'])->orWhereHas('devolucion', fn ($r) => $r->where('estado', 'PENDIENTE_REVISION')))->when($loanId, fn ($q) => $q->where('id', '<>', $loanId))->orderBy('id')->lockForUpdate()->get() : collect();
        if ($loan) {
            $loans->push($loan);
        }
        $ids = $loans->pluck('id');
        $deliveries = EntregaPrestamo::whereIn('prestamo_id', $ids)->orderBy('id')->lockForUpdate()->get();
        $returns = DevolucionPrestamo::whereIn('prestamo_id', $ids)->orderBy('id')->lockForUpdate()->get();
        $incidents = IncidenciaMantenimiento::where('recurso_id', $resource->getKey())->where(fn ($q) => $q->where('estado', 'ABIERTA')->when($incidentId, fn ($q) => $q->orWhere('id', $incidentId)))->orderBy('id')->lockForUpdate()->get();

        return compact('resource', 'loan', 'loans', 'blocks', 'reservations', 'deliveries', 'returns', 'incidents');
    }

    public function statePermission(Model $resource): string
    {
        return $resource instanceof EquipamientoTecnologico ? 'equipamientos.cambiar_estado' : 'espacios.cambiar_estado';
    }

    public function restricted(array $context): bool
    {
        return $context['incidents']->contains(fn ($i) => $i->estado->value === 'ABIERTA')
         || $context['returns']->contains(fn ($r) => $r->estado->value === 'PENDIENTE_REVISION');
    }

    public function recoveryState(array $context): string
    {
        $resource = $context['resource'];
        if ($resource instanceof EquipamientoTecnologico) {
            if ($this->restricted($context)) {
                return 'EN_MANTENIMIENTO';
            }
            if ($context['loans']->contains(fn ($p) => $p->estado->value === 'ENTREGADO')) {
                return 'PRESTADO';
            }
        } elseif ($this->restricted($context) || $context['blocks']->contains(fn ($b) => $b->inicio <= now() && $b->fin > now())) {
            throw new EquipmentConflictException('El espacio conserva una incidencia o un bloqueo vigente. No puede habilitarse.');
        }

        return 'DISPONIBLE';
    }

    public function setState(Model $resource, string $state): void
    {
        $resource->forceFill(['estado' => $state])->save();
    }

    public function restore(Usuario $actor, array $context, bool $ready): void
    {
        if (! $ready) {
            return;
        }
        Gate::forUser($actor)->authorize($this->statePermission($context['resource']));
        if ($context['resource'] instanceof EquipamientoTecnologico && $context['resource']->estado->value === 'BAJA') {
            return; // Closing a restriction never withdraws an administrative retirement.
        }
        if ($context['resource'] instanceof EspacioAcademico &&
            ($this->restricted($context) || $context['blocks']->contains(fn ($b) => $b->inicio <= now() && $b->fin > now()))) {
            return;
        }
        $state = $this->recoveryState($context);
        $this->setState($context['resource'], $state);
    }

    public function assertSpaceCanEnable(EspacioAcademico $space): void
    {
        $this->recoveryState($this->related($space));
    }
}
