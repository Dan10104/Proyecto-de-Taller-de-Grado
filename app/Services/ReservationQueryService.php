<?php

namespace App\Services;

use App\Enums\EstadoReserva;
use App\Models\EspacioAcademico;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Policies\ReservaPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ReservationQueryService
{
    public function scoped(Usuario $actor, bool $administrative): Builder
    {
        Gate::forUser($actor)->authorize($administrative ? 'reservas.ver_administrativas' : 'reservas.ver_propias');

        return Reserva::query()->when(! $administrative, fn ($q) => $q->where('solicitante_id', $actor->id));
    }

    public function filtered(Usuario $actor, bool $administrative, array $filters): Builder
    {
        $query = $this->scoped($actor, $administrative);
        foreach (['estado', 'espacio_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if ($administrative && ! empty($filters['solicitante_id'])) {
            $query->where('solicitante_id', $filters['solicitante_id']);
        }
        if (! empty($filters['fecha'])) {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $filters['fecha'], 'America/La_Paz');
            $query->where('fin', '>', $start->format('Y-m-d H:i:s'));
            if ($filters['fecha'] !== '9999-12-31') {
                $query->where('inicio', '<', $start->addDay()->format('Y-m-d H:i:s'));
            }
        }

        return $query->with(['espacio.recurso', 'espacio.piso', 'espacio.tipo', 'solicitante:id,nombres,apellidos']);
    }

    public function options(Usuario $actor, bool $administrative): array
    {
        $scope = $this->scoped($actor, $administrative);
        $spaces = EspacioAcademico::whereIn('recurso_id', (clone $scope)->select('espacio_id'))->with(['recurso', 'piso', 'tipo'])->orderBy('recurso_id')->get();

        return ['estados' => array_column(EstadoReserva::cases(), 'value'), 'espacios' => $spaces->map(fn ($s) => $this->space($s)),
            'solicitantes' => $administrative ? Usuario::whereIn('id', (clone $scope)->select('solicitante_id'))->orderBy('apellidos')->orderBy('id')->get(['id', 'nombres', 'apellidos']) : []];
    }

    public function space(EspacioAcademico $space): array
    {
        return ['id' => $space->getKey(), 'codigo' => $space->recurso->codigo, 'nombre' => $space->recurso->nombre, 'piso' => $space->piso->nombre, 'tipo' => $space->tipo->nombre, 'capacidad' => $space->capacidad];
    }

    public function present(Reserva $reservation, Usuario $actor, array $permissions, bool $administrative, bool $detail = false): array
    {
        $result = ['id' => $reservation->id, 'codigo' => $reservation->codigo, 'espacio' => $this->space($reservation->espacio),
            'inicio' => $reservation->inicio->format('Y-m-d H:i'), 'fin' => $reservation->fin->format('Y-m-d H:i'),
            'proposito' => $reservation->proposito, 'estado' => $reservation->estado->value,
            'acciones' => app(ReservaPolicy::class)->actions($actor, $reservation, $permissions)];
        if ($administrative) {
            $result['solicitante'] = $reservation->solicitante->only(['nombres', 'apellidos']);
        }
        if ($detail) {
            $result += ['solicitada_en' => $reservation->solicitada_en->format('Y-m-d H:i:s'),
                'resuelta_en' => $reservation->resuelta_en?->format('Y-m-d H:i:s'), 'resuelta_por' => $reservation->resueltaPor?->only(['nombres', 'apellidos']),
                'motivo_rechazo' => $reservation->motivo_rechazo, 'cancelada_en' => $reservation->cancelada_en?->format('Y-m-d H:i:s'),
                'cancelada_por' => $reservation->canceladaPor?->only(['nombres', 'apellidos']), 'motivo_cancelacion' => $reservation->motivo_cancelacion];
        }

        return $result;
    }
}
