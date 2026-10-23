<?php

namespace App\Services;

use App\Enums\EstadoReserva;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\ReservationRequest;
use App\Models\EspacioAcademico;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ReservationService
{
    public function __construct(private SpaceAvailabilityService $availability) {}

    public function create(Usuario $actor, array $input): Reserva
    {
        Gate::forUser($actor)->authorize('create', Reserva::class);
        if (isset($input['proposito']) && is_string($input['proposito'])) {
            $input['proposito'] = trim($input['proposito']);
        }
        $data = Validator::make($input, ReservationRequest::inputRules())->validate();
        $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['inicio'], 'America/La_Paz');
        $end = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['fin'], 'America/La_Paz');
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->transaction(function () use ($actor, $data, $start, $end) {
                    $currentActor = $actor->fresh();
                    Gate::forUser($currentActor)->authorize('create', Reserva::class);
                    $space = EspacioAcademico::whereKey($data['espacio_id'])->lockForUpdate()->firstOrFail();
                    $this->availability->assertAvailable($space, $start, $end);
                    $reservation = new Reserva;
                    $reservation->forceFill(['espacio_id' => $space->getKey(), 'solicitante_id' => $currentActor->id,
                        'inicio' => $start, 'fin' => $end, 'proposito' => $data['proposito'], 'solicitada_en' => now(), 'estado' => EstadoReserva::PENDIENTE])->save();

                    return $reservation;
                });
            } catch (QueryException $error) {
                if (($error->errorInfo[1] ?? null) !== 1062 || ! str_contains($error->getMessage(), 'uq_reservas_codigo')) {
                    throw $error;
                }
                if ($attempt === 3) {
                    throw new ReservationConflictException('No se pudo asignar un código único a la solicitud. Intente nuevamente.');
                }
            }
        }
        throw new \LogicException('Intentos de generación agotados.');
    }

    public function approve(Usuario $actor, Reserva $target): Reserva
    {
        return $this->transition($actor, $target, 'resolve', function (Reserva $reservation, EspacioAcademico $space, Usuario $currentActor) {
            $this->requirePending($reservation);
            $this->availability->assertAvailable($space, $reservation->inicio, $reservation->fin, $reservation->id);

            return ['estado' => EstadoReserva::APROBADA->value, 'resuelta_por_id' => $currentActor->id, 'resuelta_en' => now(), 'motivo_rechazo' => null];
        });
    }

    public function reject(Usuario $actor, Reserva $target, ?string $reason = null): Reserva
    {
        $reason = $this->reason($reason, 'motivo_rechazo');

        return $this->transition($actor, $target, 'resolve', function (Reserva $reservation, EspacioAcademico $space, Usuario $currentActor) use ($reason) {
            $this->requirePending($reservation);

            return ['estado' => EstadoReserva::RECHAZADA->value, 'resuelta_por_id' => $currentActor->id, 'resuelta_en' => now(), 'motivo_rechazo' => $reason];
        });
    }

    public function cancel(Usuario $actor, Reserva $target, ?string $reason = null): Reserva
    {
        $reason = $this->reason($reason, 'motivo_cancelacion');

        return $this->transition($actor, $target, 'cancel', function (Reserva $reservation, EspacioAcademico $space, Usuario $currentActor) use ($reason) {
            if (! $reservation->estado->cancelable()) {
                throw new ReservationConflictException('La reserva ya fue procesada y su estado no permite cancelarla.');
            }
            // Read the trusted clock after every lock wait, including transaction retries.
            $now = now();
            if ($now->greaterThanOrEqualTo($reservation->inicio)) {
                throw new ReservationConflictException('La reserva solo puede cancelarse antes de su hora de inicio.');
            }

            return ['estado' => EstadoReserva::CANCELADA->value, 'cancelada_por_id' => $currentActor->id, 'cancelada_en' => $now, 'motivo_cancelacion' => $reason];
        });
    }

    private function transition(Usuario $actor, Reserva $target, string $ability, callable $fields): Reserva
    {
        // Route binding is a locator, not authority for the state at the time of writing.
        Gate::forUser($actor)->authorize($ability, $target);

        return $this->transaction(function () use ($actor, $target, $ability, $fields) {
            $currentActor = $actor->fresh();
            $space = EspacioAcademico::whereKey($target->espacio_id)->lockForUpdate()->firstOrFail();
            $reservation = Reserva::whereKey($target->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($currentActor)->authorize($ability, $reservation);
            if ((int) $reservation->espacio_id !== (int) $space->getKey()) {
                throw new ReservationConflictException('La solicitud cambió. Actualice la página antes de continuar.');
            }
            $changes = $fields($reservation, $space, $currentActor);
            $affected = Reserva::whereKey($reservation->id)->where('estado', $reservation->estado->value)->update($changes);
            if ($affected !== 1) {
                throw new ReservationConflictException('La operación ya fue procesada. Actualice la página.');
            }

            return $reservation->forceFill($changes)->syncOriginal();
        });
    }

    private function transaction(callable $operation): mixed
    {
        try {
            return DB::transaction(function () use ($operation) {
                // Preserve the existing security -> space -> reservation lock order.
                Rol::where('nombre', 'Administrador')->lockForUpdate()->firstOrFail();

                return $operation();
            }, 3);
        } catch (QueryException $error) {
            if (in_array($error->errorInfo[1] ?? null, [1205, 1213], true)) {
                throw new ReservationConflictException('Otra operación está procesando este recurso. Intente nuevamente.');
            }
            throw $error;
        }
    }

    private function requirePending(Reserva $reservation): void
    {
        if ($reservation->estado !== EstadoReserva::PENDIENTE) {
            throw new ReservationConflictException('Esta solicitud ya fue procesada y no puede volver a resolverse.');
        }
    }

    private function reason(?string $reason, string $field): ?string
    {
        $reason = $reason === null ? null : trim($reason);
        $reason = $reason === '' ? null : $reason;
        Validator::make([$field => $reason], [$field => ['nullable', 'string', 'max:500']])->validate();

        return $reason;
    }
}
