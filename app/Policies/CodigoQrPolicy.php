<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\AuthorizationService;

class CodigoQrPolicy
{
    public function __construct(private AuthorizationService $authorization) {}

    public function useSpace(Usuario $actor, Reserva $reservation): bool
    {
        $permissions = $this->authorization->permissions($actor);
        if (! in_array('qr.validar_uso_espacio', $permissions, true)) {
            return false;
        }

        return in_array('reservas.ver_administrativas', $permissions, true)
            || (in_array('reservas.ver_propias', $permissions, true)
                && $reservation->solicitante_id === $actor->id
                && $actor->rolesActivos()->whereIn('nombre', ['Docente', 'Estudiante'])->exists());
    }
}
