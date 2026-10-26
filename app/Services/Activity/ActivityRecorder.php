<?php

namespace App\Services\Activity;

use App\Enums\ActivityAction;
use App\Enums\ResultadoRegistroActividad;
use App\Models\RegistroActividad;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ActivityRecorder
{
    public const CONTEXT = ['usuario_afectado_id', 'recurso_id', 'reserva_id', 'prestamo_id', 'incidencia_id'];

    public const PROCESSES = ['scheduler:vencimientos', 'scheduler:liberacion'];

    public function record(array $attributes): RegistroActividad
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('La actividad requiere la transacción de su operación.');
        }
        $allowed = [...self::CONTEXT, 'actor_id', 'accion', 'fecha_hora', 'resultado', 'descripcion', 'proceso_responsable', 'critica'];
        if (array_diff(array_keys($attributes), $allowed)) {
            throw new InvalidArgumentException('Campos de actividad no permitidos.');
        }
        $action = ActivityAction::tryFrom($attributes['accion'] ?? '');
        $result = ResultadoRegistroActividad::tryFrom($attributes['resultado'] ?? 'EXITO');
        $critical = $attributes['critica'] ?? false;
        if (! $action || ! $result || ! is_bool($critical) || $critical !== $action->critical()
            || ($action->legacy() && $result !== ResultadoRegistroActividad::EXITO)
            || ($result === ResultadoRegistroActividad::RECHAZO && ! $action->rejectable())) {
            throw new InvalidArgumentException('Acción, resultado o criticidad incoherente.');
        }
        $process = $attributes['proceso_responsable'] ?? null;
        if (empty($attributes['actor_id']) && $process === null) {
            throw new InvalidArgumentException('La actividad requiere un responsable.');
        }
        if ($process !== null && ! in_array($process, self::PROCESSES, true)) {
            throw new InvalidArgumentException('Proceso no permitido.');
        }
        foreach (['actor_id', ...self::CONTEXT] as $field) {
            if (isset($attributes[$field]) && (! is_int($attributes[$field]) || $attributes[$field] < 1)) {
                throw new InvalidArgumentException('Referencia de actividad inválida.');
            }
        }
        $description = $attributes['descripcion'] ?? null;
        if ($description !== null && (! is_string($description) || mb_strlen($description) > 500)) {
            throw new InvalidArgumentException('Descripción de actividad inválida.');
        }
        if (! $action->legacy() && $description !== null && ActivityDescription::safe($action, $description) === null) {
            throw new InvalidArgumentException('La descripción debe proceder de una plantilla segura.');
        }
        $activity = new RegistroActividad;
        $activity->forceFill($attributes + array_fill_keys(self::CONTEXT, null) + [
            'actor_id' => null, 'proceso_responsable' => null, 'descripcion' => null,
            'resultado' => 'EXITO', 'critica' => false, 'fecha_hora' => now()->startOfSecond(),
        ])->save();

        return $activity;
    }

    public function success(ActivityAction $action, Usuario $actor, array $context = [], array $facts = []): RegistroActividad
    {
        return $this->record([...$context, 'actor_id' => (int) $actor->id, 'accion' => $action->value,
            'descripcion' => ActivityDescription::make($action, $facts)]);
    }
}
