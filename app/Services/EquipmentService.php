<?php

namespace App\Services;

use App\Enums\EstadoEquipamiento;
use App\Exceptions\EquipmentConflictException;
use App\Http\Requests\EquipmentRequest;
use App\Models\EquipamientoTecnologico;
use App\Models\RecursoInstitucional;
use App\Models\TipoEquipamiento;
use App\Models\Usuario;
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
        try {
            return $this->operations->transaction($actor, 'equipamientos.gestionar', function ($actor) use ($target, $data) {
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
                if ($state !== $previous) {
                    $this->validateState($target ? $this->operations->related($equipment) : null, $state);
                }
                $resource->fill(['codigo' => $data['codigo'], 'nombre' => $data['nombre']])->save();
                $equipment->forceFill([...array_intersect_key($data, array_flip(['tipo_equipamiento_id', 'espacio_base_id', 'descripcion', 'marca', 'modelo', 'numero_serie', 'referencia_ubicacion'])), 'estado' => $state, 'recurso_id' => $resource->id])->save();

                return $equipment;
            });
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
        $this->operations->transaction($actor, 'equipamientos.cambiar_estado', function ($actor) use ($target, $state) {
            $context = $this->operations->lock($target->getKey(), true);
            $this->validateState($context, $state);
            $this->operations->setState($context['resource'], $state);
        });
    }

    private function validateState(?array $context, string $state): void
    {
        if ($state === 'PRESTADO') {
            throw new EquipmentConflictException('El estado Prestado se registra únicamente mediante una entrega.');
        }
        if ($context && $state === 'DISPONIBLE' && $this->operations->recoveryState($context) !== 'DISPONIBLE') {
            throw new EquipmentConflictException('No puede habilitarse mientras conserve una entrega, revisión o incidencia pendiente.');
        }
    }

    public function saveType(Usuario $actor, ?TipoEquipamiento $target, array $data): TipoEquipamiento
    {
        Validator::make($data, ['codigo' => 'required|string|max:60', 'nombre' => 'required|string|max:120'])->validate();
        try {
            return $this->operations->transaction($actor, 'equipamientos.gestionar', function () use ($target, $data) {
                $type = $target ? TipoEquipamiento::whereKey($target->id)->lockForUpdate()->firstOrFail() : new TipoEquipamiento;
                $type->forceFill(['codigo' => trim($data['codigo']), 'nombre' => trim($data['nombre'])])->save();

                return $type;
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['codigo' => 'El código de tipo ya existe.']);
            }
            throw $e;
        }
    }
}
