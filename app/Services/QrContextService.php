<?php

namespace App\Services;

use App\Models\EquipamientoTecnologico;
use App\Models\EspacioAcademico;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\SesionAcceso;
use App\Models\Usuario;
use App\Services\Activity\ActivityAttempt;
use Illuminate\Support\Facades\Gate;

class QrContextService
{
    public function __construct(private ResourceOperationService $operations) {}

    public function transaction(Usuario $actor, string $permission, callable $work, ?string $sessionHash = null, ?ActivityAttempt $audit = null): mixed
    {
        return $this->operations->transaction($actor, $permission, function ($fresh) use ($work, $sessionHash, $permission) {
            // Web identity is checked again after waiting for RBAC, and held through commit.
            if ($sessionHash !== null) {
                $fresh = Usuario::whereKey($fresh->id)->lockForUpdate()->firstOrFail();
                $session = SesionAcceso::where('usuario_id', $fresh->id)->where('token_hash', $sessionHash)->lockForUpdate()->first();
                abort_unless($session && ! $session->revocada_en && $session->expira_en->isAfter(now()), 403, 'La sesión ya no permite esta operación.');
            }

            Gate::forUser($fresh)->authorize($permission);

            return $work($fresh);
        }, $audit);
    }

    public function target(Usuario $actor, string $type, int $id, bool $lock = false): array
    {
        abort_unless(in_array($type, ['recurso', 'reserva', 'prestamo'], true), 404);
        $gate = Gate::forUser($actor);
        $gate->authorize('qr.gestionar');
        if ($type !== 'recurso') {
            $gate->authorize($type === 'reserva' ? 'reservas.ver_administrativas' : 'prestamos.ver_administrativos');
        }
        $operation = match ($type) {
            'reserva' => Reserva::findOrFail($id),
            'prestamo' => Prestamo::findOrFail($id),
            default => null,
        };
        $resourceId = match ($type) {
            'reserva' => $operation->espacio_id,
            'prestamo' => $operation->equipamiento_id,
            default => $id,
        };
        $isEquipment = $type === 'prestamo' || ($type === 'recurso' && EquipamientoTecnologico::whereKey($resourceId)->exists());
        if ($type === 'recurso') {
            $gate->authorize($isEquipment ? 'equipamientos.ver' : 'espacios.ver');
        }
        $query = $isEquipment ? EquipamientoTecnologico::query() : EspacioAcademico::query();
        $resource = $query->whereKey($resourceId)->when($lock, fn ($q) => $q->lockForUpdate())->firstOrFail();
        if ($operation && $lock) {
            $operation = $operation->newQuery()->whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless(($type === 'reserva' ? $operation->espacio_id : $operation->equipamiento_id) === $resource->getKey(), 409);
        }

        return ['type' => $type, 'id' => $id, 'field' => $type.'_id', 'resource' => $resource, 'operation' => $operation];
    }
}
