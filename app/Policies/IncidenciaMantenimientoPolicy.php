<?php

namespace App\Policies;

use App\Models\IncidenciaMantenimiento;
use App\Models\Usuario;
use App\Services\AuthorizationService;

class IncidenciaMantenimientoPolicy
{
    public function __construct(private AuthorizationService $auth) {}

    public function viewAny(Usuario $u): bool
    {
        return $this->auth->allows($u, 'incidencias.ver');
    }

    public function view(Usuario $u, IncidenciaMantenimiento $i): bool
    {
        return $this->viewAny($u);
    }

    public function create(Usuario $u): bool
    {
        return $this->auth->allows($u, 'incidencias.gestionar');
    }

    public function close(Usuario $u, IncidenciaMantenimiento $i): bool
    {
        return $this->auth->allows($u, 'incidencias.cerrar');
    }
}
