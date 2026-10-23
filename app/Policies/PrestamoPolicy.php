<?php

namespace App\Policies;

use App\Models\Prestamo;
use App\Models\Usuario;
use App\Services\AuthorizationService;

class PrestamoPolicy
{
    public function __construct(private AuthorizationService $auth) {}

    public function viewAny(Usuario $u): bool
    {
        return $this->auth->allows($u, 'prestamos.ver_propios') || $this->auth->allows($u, 'prestamos.ver_administrativos');
    }

    public function view(Usuario $u, Prestamo $p): bool
    {
        return $this->auth->allows($u, 'prestamos.ver_administrativos') || ($p->solicitante_id === $u->id && $this->auth->allows($u, 'prestamos.ver_propios'));
    }

    public function create(Usuario $u): bool
    {
        return $this->auth->allows($u, 'prestamos.solicitar');
    }

    public function resolve(Usuario $u, Prestamo $p): bool
    {
        return $this->auth->allows($u, 'prestamos.resolver');
    }

    public function deliver(Usuario $u, Prestamo $p): bool
    {
        return $this->auth->allows($u, 'prestamos.registrar_entrega');
    }

    public function receive(Usuario $u, Prestamo $p): bool
    {
        return $this->auth->allows($u, 'prestamos.registrar_devolucion');
    }
}
