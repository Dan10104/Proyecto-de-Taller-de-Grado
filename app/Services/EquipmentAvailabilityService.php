<?php

namespace App\Services;

use App\Exceptions\EquipmentConflictException;
use App\Models\EquipamientoTecnologico;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class EquipmentAvailabilityService
{
    public function matching(array $filters): Builder
    {
        $q = EquipamientoTecnologico::with(['recurso', 'tipo', 'espacioBase.recurso', 'espacioBase.piso']);
        if (! empty($filters['q'])) {
            $text = $filters['q'];
            $q->whereHas('recurso', fn ($r) => $r->where(fn ($r) => $r->where('codigo', 'like', "%$text%")->orWhere('nombre', 'like', "%$text%")));
        }
        foreach (['tipo_equipamiento_id', 'estado', 'espacio_base_id'] as $field) {
            if (! empty($filters[$field])) {
                $q->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['piso_id'])) {
            $q->whereHas('espacioBase', fn ($s) => $s->where('piso_id', $filters['piso_id']));
        }
        foreach (['marca', 'modelo'] as $field) {
            if (! empty($filters[$field])) {
                $q->where($field, 'like', '%'.$filters[$field].'%');
            }
        }

        return $q;
    }

    public function period(string $start, string $end): array
    {
        $a = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $start, 'America/La_Paz');
        $b = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $end, 'America/La_Paz');
        $this->validatePeriod($a, $b);

        return [$a, $b];
    }

    public function candidates(array $filters, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $this->validatePeriod($start, $end);

        return $this->matching($filters)->where('estado', 'DISPONIBLE')
            ->whereDoesntHave('incidencias', fn ($q) => $q->where('estado', 'ABIERTA'))
            ->whereDoesntHave('prestamos', function ($q) use ($start, $end) {
                $q->where(fn ($q) => $q->where('estado', 'ENTREGADO')
                    ->orWhereHas('devolucion', fn ($r) => $r->where('estado', 'PENDIENTE_REVISION'))
                    ->orWhere(fn ($q) => $q->whereIn('estado', ['APROBADO', 'ENTREGADO'])->where('inicio_previsto', '<', $end)->where('fin_previsto', '>', $start)));
            });
    }

    public function assertAvailable(array $context, CarbonImmutable $start, CarbonImmutable $end, ?int $except = null): void
    {
        $this->validatePeriod($start, $end);
        $conflict = $context['loans']->contains(fn ($p) => $p->id !== $except
         && ($p->estado->value === 'ENTREGADO' || (in_array($p->estado->value, ['APROBADO', 'ENTREGADO'], true) && $p->inicio_previsto < $end && $p->fin_previsto > $start)));
        if ($context['resource']->estado->value !== 'DISPONIBLE' || $conflict || app(ResourceOperationService::class)->restricted($context)) {
            throw new EquipmentConflictException('El equipo ya no está disponible para este periodo. Revise su estado, las incidencias y los préstamos vigentes.');
        }
    }

    private function validatePeriod(CarbonImmutable $start, CarbonImmutable $end): void
    {
        if ($end <= $start) {
            throw ValidationException::withMessages(['fin_previsto' => 'El fin debe ser posterior al inicio.']);
        }
    }
}
