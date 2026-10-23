<?php

namespace App\Policies;

use App\Enums\EstadoReserva;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\AuthorizationService;

class ReservaPolicy
{
    public function __construct(private AuthorizationService $authorization) {}

    public function viewAny(Usuario $user): bool
    {
        return count(array_intersect(['reservas.ver_propias', 'reservas.ver_administrativas'], $this->authorization->permissions($user))) > 0;
    }

    public function view(Usuario $user, Reserva $reserva): bool
    {
        $permissions = $this->authorization->permissions($user);

        return in_array('reservas.ver_administrativas', $permissions, true)
            || ((int) $reserva->solicitante_id === (int) $user->id && in_array('reservas.ver_propias', $permissions, true));
    }

    public function create(Usuario $user): bool
    {
        return $this->authorization->allows($user, 'reservas.solicitar');
    }

    public function resolve(Usuario $user, Reserva $reserva): bool
    {
        return $this->authorization->allows($user, 'reservas.resolver');
    }

    public function cancel(Usuario $user, Reserva $reserva): bool
    {
        return $this->permitsCancellation($user, $reserva, $this->authorization->permissions($user));
    }

    private function permitsCancellation(Usuario $user, Reserva $reserva, array $permissions): bool
    {
        return in_array('reservas.cancelar_administrativas', $permissions, true)
            || ((int) $reserva->solicitante_id === (int) $user->id && in_array('reservas.cancelar_propias', $permissions, true));
    }

    // Precomputed permissions avoid an RBAC query for each row; writes always reauthorize.
    public function actions(Usuario $user, Reserva $reserva, array $permissions): array
    {
        return ['resolver' => $reserva->estado === EstadoReserva::PENDIENTE && in_array('reservas.resolver', $permissions, true),
            'cancelar' => $this->permitsCancellation($user, $reserva, $permissions) && $reserva->estado->cancelable() && now()->lt($reserva->inicio)];
    }
}
