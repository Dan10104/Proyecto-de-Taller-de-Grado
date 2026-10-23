<?php

namespace App\Policies;

use App\Models\EquipamientoTecnologico;
use App\Models\Usuario;
use App\Services\AuthorizationService;

class EquipamientoTecnologicoPolicy
{
    public function __construct(private AuthorizationService $auth) {}

    public function viewAny(Usuario $u): bool
    {
        return $this->auth->allows($u, 'equipamientos.ver');
    }

    public function view(Usuario $u, EquipamientoTecnologico $e): bool
    {
        return $this->viewAny($u);
    }

    public function create(Usuario $u): bool
    {
        return $this->auth->allows($u, 'equipamientos.gestionar');
    }

    public function update(Usuario $u, EquipamientoTecnologico $e): bool
    {
        return $this->create($u);
    }

    public function changeState(Usuario $u, EquipamientoTecnologico $e): bool
    {
        return $this->auth->allows($u, 'equipamientos.cambiar_estado');
    }
}
