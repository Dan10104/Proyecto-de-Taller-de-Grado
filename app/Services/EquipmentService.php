<?php

namespace App\Services;

use App\Enums\ActivityAction as Action;
use App\Enums\EstadoEquipamiento;
use App\Exceptions\EquipmentConflictException;
use App\Http\Requests\EquipmentRequest;
use App\Models\EquipamientoTecnologico;
use App\Models\RecursoInstitucional;
use App\Models\TipoEquipamiento;
use App\Models\Usuario;
use App\Services\Activity\ActivityAttempt;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EquipmentService
{
    public function __construct(private ResourceOperationService $operations) {}

    public function save(Usuario $actor, ?EquipamientoTecnologico $target, array $data): EquipamientoTecnologico
    {
        $data = EquipmentRequest::normalize($data);
        Validator::make($data, EquipmentRequest::inputRules($target?->getKey()))->validate();
        $audit = new ActivityAttempt($actor);
        try {
            return $this->operations->transaction($actor, 'equipamientos.gestionar', function ($actor) use ($target, $data, $audit) {
                $equipment = $target ? EquipamientoTecnologico::whereKey($target->getKey())->lockForUpdate()->firstOrFail() : new EquipamientoTecnologico;
                $previous = $target ? $equipment->estado->value : 'DISPONIBLE';
                $state = $data['estado'] ?? $previous;
                if ($state !== $previous) {
                    Gate::forUser($actor)->authorize('equipamientos.cambiar_estado');
                }
                $resource = $target ? RecursoInstitucional::whereKey($equipment->getKey())->lockForUpdate()->firstOrFail() : new RecursoInstitucional;
                if ($target && $state === 'DISPONIBLE' && $state !== $previous && empty($data['apto'])) {
                    throw ValidationException::withMessages(['apto' => 'Confirme la aptitud operativa del equipo.']);
                }
                $action = ! $target ? Action::EQUIPAMIENTO_CREADO : ($state !== $previous ? Action::EQUIPAMIENTO_ESTADO_CAMBIADO : Action::EQUIPAMIENTO_EDITADO);
                $audit->admit($action, $target ? ['recurso_id' => (int) $equipment->getKey()] : []);
                if ($state !== $previous) {
                    $this->validateState($target ? $this->operations->related($equipment) : null, $state, $audit);
                }
                $resource->fill(['codigo' => $data['codigo'], 'nombre' => $data['nombre']]);
                $fields = array_keys($resource->getDirty());
                $resource->save();
                $equipment->forceFill([...array_intersect_key($data, array_flip(['tipo_equipamiento_id', 'espacio_base_id', 'descripcion', 'marca', 'modelo', 'numero_serie', 'referencia_ubicacion'])), 'estado' => $state, 'recurso_id' => $resource->id]);
                $fields = array_values(array_unique([...$fields, ...array_diff(array_keys($equipment->getDirty()), ['recurso_id'])]));
                $equipment->save();
                if (! $target || $fields) {
                    app(ActivityRecorder::class)->success($action, $actor, ['recurso_id' => (int) $equipment->getKey()], ['campos' => $fields, 'anterior' => $previous, 'nuevo' => $state]);
                }

                return $equipment;
            }, $audit);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages([str_contains($e->getMessage(), 'uq_equipamiento_numero_serie') ? 'numero_serie' : 'codigo' => 'Este valor ya está registrado.']);
            }
            throw $e;
        }
    }

    public function state(Usuario $actor, EquipamientoTecnologico $target, string $state, bool $ready = false): void
    {
        if ($state === 'DISPONIBLE' && ! $ready) {
            throw ValidationException::withMessages(['apto' => 'Confirme la aptitud operativa del equipo.']);
        }
        Validator::make(['estado' => $state], ['estado' => ['required', Rule::enum(EstadoEquipamiento::class)]])->validate();
        $audit = new ActivityAttempt($actor);
        $this->operations->transaction($actor, 'equipamientos.cambiar_estado', function ($actor) use ($target, $state, $audit) {
            $context = $this->operations->lock($target->getKey(), true);
            $previous = $context['resource']->estado->value;
            if ($previous !== $state) {
                $audit->admit(Action::EQUIPAMIENTO_ESTADO_CAMBIADO, ['recurso_id' => (int) $target->getKey()]);
            }
            $this->validateState($context, $state, $audit);
            if ($previous === $state) {
                return;
            }
            $this->operations->setState($context['resource'], $state);
            app(ActivityRecorder::class)->success(Action::EQUIPAMIENTO_ESTADO_CAMBIADO, $actor, $audit->context, ['anterior' => $previous, 'nuevo' => $state]);
        }, $audit);
    }

    private function validateState(?array $context, string $state, ActivityAttempt $audit): void
    {
        if ($state === 'PRESTADO') {
            $audit->reject(new EquipmentConflictException('El estado Prestado se registra únicamente mediante una entrega.'));
        }
        if ($context && $state === 'DISPONIBLE' && $this->operations->recoveryState($context) !== 'DISPONIBLE') {
            $audit->reject(new EquipmentConflictException('No puede habilitarse mientras conserve una entrega, revisión o incidencia pendiente.'));
        }
    }

    public function saveType(Usuario $actor, ?TipoEquipamiento $target, array $data): TipoEquipamiento
    {
        Validator::make($data, ['codigo' => 'required|string|max:60', 'nombre' => 'required|string|max:120'])->validate();
        $audit = new ActivityAttempt($actor);
        try {
            return $this->operations->transaction($actor, 'equipamientos.gestionar', function () use ($actor, $target, $data, $audit) {
                $type = $target ? TipoEquipamiento::whereKey($target->id)->lockForUpdate()->firstOrFail() : new TipoEquipamiento;
                $audit->admit($target ? Action::TIPO_EQUIPAMIENTO_EDITADO : Action::TIPO_EQUIPAMIENTO_CREADO);
                $type->forceFill(['codigo' => trim($data['codigo']), 'nombre' => trim($data['nombre'])]);
                $fields = array_keys($type->getDirty());
                $type->save();
                if (! $target || $fields) {
                    app(ActivityRecorder::class)->success($audit->action, $actor, [], ['id' => (int) $type->id, 'campos' => $fields]);
                }

                return $type;
            }, $audit);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['codigo' => 'El código de tipo ya existe.']);
            }
            throw $e;
        }
    }
}
