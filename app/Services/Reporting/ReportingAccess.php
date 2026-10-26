<?php

namespace App\Services\Reporting;

use App\Models\Usuario;
use App\Services\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class ReportingAccess
{
    public function __construct(public int $userId, public array $permissions, public array $roles) {}

    public static function for(Usuario $actor): self
    {
        $actor = $actor->fresh();
        if (! $actor) {
            throw new AuthorizationException;
        }

        return new self((int) $actor->id, app(AuthorizationService::class)->permissions($actor), $actor->rolesActivos()->pluck('nombre')->unique()->sort()->values()->all());
    }

    public function has(string ...$permissions): bool
    {
        return array_diff($permissions, $this->permissions) === [];
    }

    public function require(string ...$permissions): void
    {
        if (! $this->has(...$permissions)) {
            throw new AuthorizationException('No tiene permiso para esta consulta.');
        }
    }

    public function administrative(): bool
    {
        return array_intersect($this->roles, ['Administrador', 'Encargado de Laboratorio']) !== [];
    }

    public function personal(): bool
    {
        return array_intersect($this->roles, ['Docente', 'Estudiante']) !== [];
    }

    public function signature(): string
    {
        $permissions = $this->permissions;
        sort($permissions);

        return hash('sha256', json_encode([$this->userId, $permissions, $this->roles], JSON_THROW_ON_ERROR));
    }

    public function revalidate(Usuario $actor): void
    {
        if (! hash_equals($this->signature(), self::for($actor)->signature())) {
            throw new AuthorizationException('Sus permisos cambiaron. Actualice la vista antes de continuar.');
        }
    }
}
