<?php

namespace App\Policies;

use App\Models\EspacioAcademico;
use App\Models\Usuario;
use App\Services\AuthorizationService;

class EspacioAcademicoPolicy
{
    public function __construct(private AuthorizationService $authorization) {}

    public function view(Usuario $user, EspacioAcademico $space): bool
    {
        return $this->authorization->allows($user, 'espacios.ver');
    }

    public function create(Usuario $user): bool
    {
        return $this->authorization->allows($user, 'espacios.gestionar');
    }

    public function update(Usuario $user, EspacioAcademico $space): bool
    {
        return $this->authorization->allows($user, 'espacios.gestionar');
    }

    public function changeState(Usuario $user, EspacioAcademico $space): bool
    {
        return $this->authorization->allows($user, 'espacios.cambiar_estado');
    }

    public function block(Usuario $user, EspacioAcademico $space): bool
    {
        return $this->authorization->allows($user, 'espacios.gestionar_bloqueos');
    }
}
