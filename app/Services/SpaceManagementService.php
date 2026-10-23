<?php

namespace App\Services;

use App\Enums\EstadoEspacio;
use App\Models\BloqueoEspacio;
use App\Models\EspacioAcademico;
use App\Models\RecursoInstitucional;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SpaceManagementService
{
    public function change(Usuario $actor, string $permission, callable $operation, string $uniqueField = 'codigo'): mixed
    {
        try {
            return DB::transaction(function () use ($actor, $permission, $operation) {
                // Same lock as security administration: permission withdrawal cannot race a mutation.
                Rol::where('nombre', 'Administrador')->lockForUpdate()->firstOrFail();
                Gate::forUser($actor->fresh())->authorize($permission);

                return $operation();
            }, 3);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages([$uniqueField => 'Este valor ya está registrado. Utilice uno diferente.']);
            }
            throw $e;
        }
    }

    public function save(Usuario $actor, ?EspacioAcademico $target, array $data): EspacioAcademico
    {
        return $this->change($actor, 'espacios.gestionar', function () use ($actor, $target, $data) {
            $space = $target ? EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail() : new EspacioAcademico;
            Gate::forUser($actor->fresh())->authorize($target ? 'update' : 'create', $target ? $space : EspacioAcademico::class);
            $previous = $target ? $space->estado->value : EstadoEspacio::DISPONIBLE->value;
            if (isset($data['estado']) && $data['estado'] !== $previous) {
                Gate::forUser($actor->fresh())->authorize('espacios.cambiar_estado');
            }
            $resource = $target ? RecursoInstitucional::whereKey($space->getKey())->lockForUpdate()->firstOrFail() : new RecursoInstitucional;
            if ($target && ($data['estado'] ?? $previous) === 'DISPONIBLE' && $previous !== 'DISPONIBLE') {
                app(ResourceOperationService::class)->assertSpaceCanEnable($space);
            }
            $resource->fill(['codigo' => $data['codigo'], 'nombre' => $data['nombre']])->save();
            if (! $target) {
                $space->recurso_id = $resource->id;
            }
            $space->fill(array_intersect_key($data, array_flip(['piso_id', 'tipo_espacio_id', 'capacidad', 'referencia_ubicacion', 'estado'])))->save();
            $space->caracteristicas()->sync($data['caracteristicas'] ?? []);

            return $space;
        });
    }

    public function state(Usuario $actor, EspacioAcademico $target, string $state): void
    {
        $this->change($actor, 'espacios.cambiar_estado', function () use ($actor, $target, $state) {
            $space = EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('changeState', $space);
            if ($state === 'DISPONIBLE') {
                app(ResourceOperationService::class)->assertSpaceCanEnable($space);
            }
            $space->update(['estado' => $state]);
        });
    }

    public function block(Usuario $actor, EspacioAcademico $target, array $data): BloqueoEspacio
    {
        return $this->change($actor, 'espacios.gestionar_bloqueos', function () use ($actor, $target, $data) {
            $space = EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('block', $space);
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['inicio'], 'America/La_Paz');
            $end = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['fin'], 'America/La_Paz');
            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages(['fin' => 'El fin debe ser posterior al inicio.']);
            }
            $block = new BloqueoEspacio;
            $block->forceFill(['espacio_id' => $space->getKey(), 'responsable_id' => $actor->id, 'inicio' => $start, 'fin' => $end, 'motivo' => $data['motivo'], 'activo' => true])->save();

            return $block;
        });
    }

    public function deactivate(Usuario $actor, EspacioAcademico $target, int $blockId): void
    {
        $this->change($actor, 'espacios.gestionar_bloqueos', function () use ($actor, $target, $blockId) {
            $space = EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('block', $space);
            $block = $space->bloqueos()->whereKey($blockId)->lockForUpdate()->firstOrFail();
            $block->forceFill(['activo' => false])->save();
        });
    }
}
