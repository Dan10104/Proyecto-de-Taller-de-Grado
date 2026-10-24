<?php

namespace App\Services;

use App\Enums\EstadoSensorIot;
use App\Exceptions\IotException;
use App\Models\EspacioAcademico;
use App\Models\RecursoInstitucional;
use App\Models\SensorIot;
use App\Models\Usuario;
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

        return $this->security->change($actor, 'iot.sensores.gestionar', function () use ($target, $data) {
            $sensor = $target ? SensorIot::whereKey($target->id)->lockForUpdate()->firstOrFail() : new SensorIot;
            $this->lockSpace((int) $data['espacio_actual_id']);
            $sensor->forceFill($data)->save();

            return $sensor->refresh();
        }, 'identificador');
    }

    public function state(Usuario $actor, SensorIot $target, string $state): void
    {
        Validator::make(['estado' => $state], ['estado' => ['required', Rule::enum(EstadoSensorIot::class)]])->validate();
        $this->security->change($actor, 'iot.sensores.gestionar', function () use ($target, $state) {
            $sensor = SensorIot::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->lockSpace($sensor->espacio_actual_id);
            $sensor->forceFill(['estado' => $state])->save();
        });
    }

    public function provision(Usuario $actor, SensorIot $target): string
    {
        return $this->security->change($actor, 'iot.sensores.gestionar', function () use ($target) {
            $sensor = SensorIot::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->lockSpace($sensor->espacio_actual_id);
            if ($sensor->estado !== EstadoSensorIot::HABILITADO) {
                throw new IotException(403, 'device_not_authorized');
            }

            return bin2hex($this->credentials->key($sensor->identificador));
        });
    }

    private function lockSpace(int $id): void
    {
        EspacioAcademico::whereKey($id)->sharedLock()->firstOrFail();
        RecursoInstitucional::whereKey($id)->sharedLock()->firstOrFail();
    }
}
