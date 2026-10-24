<?php

namespace App\Services;

use App\Enums\EstadoSensorIot;
use App\Exceptions\IotException;
use App\Models\EspacioAcademico;
use App\Models\EventoOcupacion;
use App\Models\RecursoInstitucional;
use App\Models\SensorIot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IotIngestionService
{
    public function __construct(private IotCredentials $credentials, private IotPayload $payload, private IotRateLimiter $limiter) {}

    public function receive(Request $request, #[\SensitiveParameter] string $raw): array
    {
        $identifier = $this->header($request, 'X-IoT-Sensor', '/\A[A-Z0-9][A-Z0-9_-]{0,119}\z/D');
        $timestamp = $this->header($request, 'X-IoT-Timestamp', '/\A[1-9][0-9]{0,11}\z/D');
        $signature = $this->header($request, 'X-IoT-Signature', '/\A[0-9a-f]{64}\z/D');
        $this->credentials->checkClock($timestamp);
        $expected = hash_hmac('sha256', $this->credentials->canonical($identifier, $timestamp, $raw), $this->credentials->key($identifier));
        if (! hash_equals($expected, $signature)) {
            throw new IotException(401, 'authentication_failed');
        }
        $sensor = SensorIot::where('identificador', $identifier)->first();
        if (! $sensor || $sensor->identificador !== $identifier) {
            throw new IotException(401, 'authentication_failed');
        }
        $this->limiter->consume('sensor:'.$sensor->id, config('iot.rate_limit_per_minute'));
        if ($sensor->estado !== EstadoSensorIot::HABILITADO) {
            throw new IotException(403, 'device_not_authorized');
        }
        $data = $this->payload->parse($raw, $identifier);

        return $this->persist($sensor->id, $identifier, $timestamp, $data);
    }

    private function header(Request $request, string $name, string $pattern): string
    {
        $values = $request->headers->all($name);
        if (count($values) !== 1 || ! preg_match($pattern, $values[0])) {
            throw new IotException(401, 'authentication_failed');
        }

        return $values[0];
    }

    private function persist(int $id, string $identifier, string $timestamp, array $data): array
    {
        // A new transaction is essential: the first consistent read must occur
        // AFTER the per-sensor mutex, including under MySQL REPEATABLE READ.
        if (DB::transactionLevel() !== 0) {
            throw new IotException(503, 'temporarily_unavailable');
        }

        return DB::transaction(function () use ($id, $identifier, $timestamp, $data) {
            $sensor = SensorIot::whereKey($id)->lockForUpdate()->first();
            if (! $sensor || $sensor->identificador !== $identifier) {
                throw new IotException(401, 'authentication_failed');
            }
            if ($sensor->estado !== EstadoSensorIot::HABILITADO) {
                throw new IotException(403, 'device_not_authorized');
            }
            $space = EspacioAcademico::whereKey($sensor->espacio_actual_id)->sharedLock()->firstOrFail();
            $resource = RecursoInstitucional::whereKey($space->recurso_id)->sharedLock()->firstOrFail();
            if ($resource->codigo !== $data['space_code']) {
                throw new IotException(409, 'space_context_mismatch');
            }
            $this->credentials->checkClock($timestamp);
            $this->payload->checkEventClock($data['occurred_at']);
            $event = EventoOcupacion::where('sensor_id', $id)->where('fecha_evento', $data['occurred_at']->format('Y-m-d H:i:s'))
                ->where('espacio_historico_id', $space->recurso_id)->where('tipo', $data['type'])->whereRaw('valor <=> ?', [$data['value']])->first();
            if ($event) {
                return ['status' => 200, 'result' => 'duplicate', 'event_id' => $event->id];
            }
            $event = new EventoOcupacion;
            $event->forceFill(['sensor_id' => $id, 'espacio_historico_id' => $space->recurso_id, 'tipo' => $data['type'], 'valor' => $data['value'], 'fecha_evento' => $data['occurred_at']])->save();
            $event->refresh(); // DEFAULT CURRENT_TIMESTAMP is the server acceptance time.
            $sensor->forceFill(['ultima_comunicacion' => $event->fecha_recepcion])->save();

            return ['status' => 201, 'result' => 'accepted', 'event_id' => $event->id];
        }, 3);
    }
}
