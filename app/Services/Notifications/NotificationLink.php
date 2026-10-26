<?php

namespace App\Services\Notifications;

use App\Models\IncidenciaMantenimiento;
use App\Models\Notificacion;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;

class NotificationLink
{
    public function resolve(Notificacion $n, Usuario $user): ?array
    {
        $a = $n->registroActividad;
        if ($a->incidencia_id && ($i = IncidenciaMantenimiento::find($a->incidencia_id)) && Gate::forUser($user)->allows('view', $i)) {
            return ['url' => route('incidencias.show', $i, false), 'label' => 'Consultar incidencia o mantenimiento'];
        }
        if ($a->prestamo_id && ($p = Prestamo::find($a->prestamo_id)) && Gate::forUser($user)->allows('view', $p)) {
            return ['url' => route('prestamos.show', $p, false), 'label' => 'Consultar préstamo'];
        }
        if ($a->reserva_id && ($r = Reserva::find($a->reserva_id)) && Gate::forUser($user)->allows('view', $r)) {
            return ['url' => route('reservas.show', $r, false), 'label' => 'Consultar reserva'];
        }

        return null;
    }
}
