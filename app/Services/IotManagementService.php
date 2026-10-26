<?php

namespace App\Services;

use App\Enums\ActivityAction as Action;
use App\Enums\EstadoSensorIot;
use App\Exceptions\IotException;
use App\Models\EspacioAcademico;
use App\Models\RecursoInstitucional;
use App\Models\SensorIot;
use App\Models\Usuario;
use App\Services\Activity\ActivityAttempt;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IotManagementService
{
    public function __construct(private SpaceManagementService $security, private IotCredentials $credentials) {}

    public function save(Usuario $actor, ?SensorIot $target, array $data): SensorIot
    {
        $data = Validator::make($data, [
            'identificador' => $target ? ['prohibited'] : ['required', 'string', 'regex:/\A[A-Z0-9][A-Z0-9_-]{0,119}\z/D'],
            'tipo' => ['required', 'string', 'max:100'],
            'espacio_actual_id' => ['required', 'integer', Rule::exists('espacios_academicos', 'recurso_id')],
            'estado' => ['prohibited'], 'ultima_comunicacion' => ['prohibited'], 'id' => ['prohibited'],
        ])->validate();

        $audit = new ActivityAttempt($actor);

        return $this->security->change($actor, 'iot.sensores.gestionar', function () use ($actor, $target, $data, $audit) {
            $sensor = $target ? SensorIot::whereKey($target->id)->lockForUpdate()->firstOrFail() : new SensorIot;
            $this->lockSpace((int) $data['espacio_actual_id']);
            $previous = (int) ($sensor->espacio_actual_id ?? 0);
            $action = ! $target ? Action::SENSOR_CREADO : ($previous !== (int) $data['espacio_actual_id'] ? Action::SENSOR_REASIGNADO : Action::SENSOR_EDITADO);
            $audit->admit($action, ['recurso_id' => (int) $data['espacio_actual_id']]);
            $sensor->forceFill($data);
            $fields = array_values(array_diff(array_keys($sensor->getDirty()), ['identificador']));
            $sensor->save();
            if (! $target || $fields) {
                app(ActivityRecorder::class)->success($action, $actor, $audit->context, ['id' => (int) $sensor->id, 'anterior_id' => $previous, 'nuevo_id' => (int) $sensor->espacio_actual_id, 'campos' => $fields]);
            }

            return $sensor->refresh();
        }, 'identificador', $audit);
    }

    public function state(Usuario $actor, SensorIot $target, string $state): void
    {
        Validator::make(['estado' => $state], ['estado' => ['required', Rule::enum(EstadoSensorIot::class)]])->validate();
        $audit = new ActivityAttempt($actor);
        $this->security->change($actor, 'iot.sensores.gestionar', function () use ($actor, $target, $state, $audit) {
            $sensor = SensorIot::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->lockSpace($sensor->espacio_actual_id);
            $previous = $sensor->estado->value;
            if ($previous === $state) {
                return;
            }
            $audit->admit($state === 'HABILITADO' ? Action::SENSOR_HABILITADO : Action::SENSOR_DESHABILITADO, ['recurso_id' => (int) $sensor->espacio_actual_id]);
            $sensor->forceFill(['estado' => $state])->save();
            app(ActivityRecorder::class)->success($audit->action, $actor, $audit->context, ['id' => (int) $sensor->id, 'anterior' => $previous, 'nuevo' => $state]);
        }, audit: $audit);
    }

    public function provision(Usuario $actor, SensorIot $target): string
    {
        $audit = new ActivityAttempt($actor);

        return $this->security->change($actor, 'iot.sensores.gestionar', function () use ($actor, $target, $audit) {
            $sensor = SensorIot::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->lockSpace($sensor->espacio_actual_id);
            $audit->admit(Action::SENSOR_CREDENCIAL_CONSULTADA, ['recurso_id' => (int) $sensor->espacio_actual_id]);
            if ($sensor->estado !== EstadoSensorIot::HABILITADO) {
                $audit->reject(new IotException(403, 'device_not_authorized'));
            }

            $key = bin2hex($this->credentials->key($sensor->identificador));
            app(ActivityRecorder::class)->success(Action::SENSOR_CREDENCIAL_CONSULTADA, $actor, $audit->context, ['id' => (int) $sensor->id]);

            return $key;
        }, audit: $audit);
    }

    private function lockSpace(int $id): void
    {
        EspacioAcademico::whereKey($id)->sharedLock()->firstOrFail();
        RecursoInstitucional::whereKey($id)->sharedLock()->firstOrFail();
    }
}
