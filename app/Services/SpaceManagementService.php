<?php

namespace App\Services;

use App\Enums\ActivityAction as Action;
use App\Enums\EstadoEspacio;
use App\Exceptions\EquipmentConflictException;
use App\Models\BloqueoEspacio;
use App\Models\CaracteristicaEspacio;
use App\Models\EspacioAcademico;
use App\Models\Piso;
use App\Models\RecursoInstitucional;
use App\Models\Rol;
use App\Models\TipoEspacio;
use App\Models\Usuario;
use App\Services\Activity\ActivityAttempt;
use App\Services\Activity\ActivityRecorder;
use App\Services\Activity\ActivityTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SpaceManagementService
{
    public function change(Usuario $actor, string $permission, callable $operation, string $uniqueField = 'codigo', ?ActivityAttempt $audit = null): mixed
    {
        try {
            return app(ActivityTransaction::class)->run(function () use ($actor, $permission, $operation) {
                // Same lock as security administration: permission withdrawal cannot race a mutation.
                Rol::where('nombre', 'Administrador')->lockForUpdate()->firstOrFail();
                Gate::forUser($actor->fresh())->authorize($permission);

                return $operation();
            }, $audit);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages([$uniqueField => 'Este valor ya está registrado. Utilice uno diferente.']);
            }
            throw $e;
        }
    }

    public function save(Usuario $actor, ?EspacioAcademico $target, array $data): EspacioAcademico
    {
        $audit = new ActivityAttempt($actor);

        return $this->change($actor, 'espacios.gestionar', function () use ($actor, $target, $data, $audit) {
            $space = $target ? EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail() : new EspacioAcademico;
            Gate::forUser($actor->fresh())->authorize($target ? 'update' : 'create', $target ? $space : EspacioAcademico::class);
            $previous = $target ? $space->estado->value : EstadoEspacio::DISPONIBLE->value;
            if (isset($data['estado']) && $data['estado'] !== $previous) {
                Gate::forUser($actor->fresh())->authorize('espacios.cambiar_estado');
            }
            $resource = $target ? RecursoInstitucional::whereKey($space->getKey())->lockForUpdate()->firstOrFail() : new RecursoInstitucional;
            $action = ! $target ? Action::ESPACIO_CREADO : (($data['estado'] ?? $previous) !== $previous ? Action::ESPACIO_ESTADO_CAMBIADO : Action::ESPACIO_EDITADO);
            $audit->admit($action, $target ? ['recurso_id' => (int) $space->getKey()] : []);
            if ($target && ($data['estado'] ?? $previous) === 'DISPONIBLE' && $previous !== 'DISPONIBLE') {
                try {
                    app(ResourceOperationService::class)->assertSpaceCanEnable($space);
                } catch (EquipmentConflictException $e) {
                    $audit->reject($e);
                }
            }
            $resource->fill(['codigo' => $data['codigo'], 'nombre' => $data['nombre']]);
            $fields = array_keys($resource->getDirty());
            $resource->save();
            if (! $target) {
                $space->recurso_id = $resource->id;
            }
            $space->fill(array_intersect_key($data, array_flip(['piso_id', 'tipo_espacio_id', 'capacidad', 'referencia_ubicacion', 'estado'])));
            $fields = array_values(array_unique([...$fields, ...array_diff(array_keys($space->getDirty()), ['recurso_id'])]));
            $space->save();
            $changes = $space->caracteristicas()->sync($data['caracteristicas'] ?? []);
            if ($changes['attached'] || $changes['detached']) {
                $fields[] = 'caracteristicas';
            }
            if (! $target || $fields) {
                app(ActivityRecorder::class)->success($action, $actor, ['recurso_id' => (int) $space->getKey()], ['campos' => $fields, 'anterior' => $previous, 'nuevo' => $space->estado->value]);
            }

            return $space;
        }, audit: $audit);
    }

    public function state(Usuario $actor, EspacioAcademico $target, string $state): void
    {
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'espacios.cambiar_estado', function () use ($actor, $target, $state, $audit) {
            $space = EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('changeState', $space);
            Validator::make(['estado' => $state], ['estado' => [Rule::enum(EstadoEspacio::class)]])->validate();
            $previous = $space->estado->value;
            if ($previous !== $state) {
                $audit->admit(Action::ESPACIO_ESTADO_CAMBIADO, ['recurso_id' => (int) $space->getKey()]);
            }
            if ($state === 'DISPONIBLE') {
                try {
                    app(ResourceOperationService::class)->assertSpaceCanEnable($space);
                } catch (EquipmentConflictException $e) {
                    $audit->reject($e);
                }
            }
            if ($previous === $state) {
                return;
            }
            $space->update(['estado' => $state]);
            app(ActivityRecorder::class)->success(Action::ESPACIO_ESTADO_CAMBIADO, $actor, $audit->context, ['anterior' => $previous, 'nuevo' => $state]);
        }, audit: $audit);
    }

    public function block(Usuario $actor, EspacioAcademico $target, array $data): BloqueoEspacio
    {
        $audit = new ActivityAttempt($actor);

        return $this->change($actor, 'espacios.gestionar_bloqueos', function () use ($actor, $target, $data, $audit) {
            $space = EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('block', $space);
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['inicio'], 'America/La_Paz');
            $end = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['fin'], 'America/La_Paz');
            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages(['fin' => 'El fin debe ser posterior al inicio.']);
            }
            $audit->admit(Action::BLOQUEO_ESPACIO_CREADO, ['recurso_id' => (int) $space->getKey()]);
            $block = new BloqueoEspacio;
            $block->forceFill(['espacio_id' => $space->getKey(), 'responsable_id' => $actor->id, 'inicio' => $start, 'fin' => $end, 'motivo' => $data['motivo'], 'activo' => true])->save();

            app(ActivityRecorder::class)->success(Action::BLOQUEO_ESPACIO_CREADO, $actor, $audit->context, ['id' => (int) $block->id, 'inicio' => $start->toDateTimeString(), 'fin' => $end->toDateTimeString()]);

            return $block;
        }, audit: $audit);
    }

    public function deactivate(Usuario $actor, EspacioAcademico $target, int $blockId): void
    {
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'espacios.gestionar_bloqueos', function () use ($actor, $target, $blockId, $audit) {
            $space = EspacioAcademico::whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('block', $space);
            $block = $space->bloqueos()->whereKey($blockId)->lockForUpdate()->firstOrFail();
            if (! $block->activo) {
                return;
            }
            $audit->admit(Action::BLOQUEO_ESPACIO_DESACTIVADO, ['recurso_id' => (int) $space->getKey()]);
            $block->forceFill(['activo' => false])->save();
            app(ActivityRecorder::class)->success(Action::BLOQUEO_ESPACIO_DESACTIVADO, $actor, $audit->context, ['id' => (int) $block->id]);
        }, audit: $audit);
    }

    public function saveCatalog(Usuario $actor, string $catalog, ?int $id, array $data): void
    {
        [$class, $create, $edit, $unique] = match ($catalog) {
            'pisos' => [Piso::class, Action::PISO_CREADO, Action::PISO_EDITADO, 'numero'],
            'tipos' => [TipoEspacio::class, Action::TIPO_ESPACIO_CREADO, Action::TIPO_ESPACIO_EDITADO, 'codigo'],
            'caracteristicas' => [CaracteristicaEspacio::class, Action::CARACTERISTICA_ESPACIO_CREADA, Action::CARACTERISTICA_ESPACIO_EDITADA, 'nombre'],
        };
        $audit = new ActivityAttempt($actor);
        $this->change($actor, 'espacios.gestionar', function () use ($actor, $class, $id, $data, $create, $edit, $audit) {
            $record = $id ? $class::whereKey($id)->lockForUpdate()->firstOrFail() : new $class;
            $audit->admit($id ? $edit : $create);
            $record->fill($data);
            $fields = array_keys($record->getDirty());
            $record->save();
            if (! $id || $fields) {
                app(ActivityRecorder::class)->success($audit->action, $actor, [], ['id' => (int) $record->getKey(), 'campos' => $fields]);
            }
        }, $unique, $audit);
    }
}
