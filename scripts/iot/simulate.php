<?php

// Local signed device simulator. No credentials in arguments, output or fixtures.
use App\Models\SensorIot;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
try {
    if (! $app->environment(['local', 'testing'])) {
        throw new RuntimeException;
    }
    $identifier = $argv[1] ?? '';
    $sensor = SensorIot::where('identificador', $identifier)->first();
    if (! $sensor || $sensor->identificador !== $identifier) {
        throw new RuntimeException;
    }
    $master = config('iot.master_secret');
    if (! is_string($master) || ! preg_match('/\A[0-9a-f]{64}\z/', $master)) {
        throw new RuntimeException;
    }
    $body = json_encode(['sensor_identifier' => $identifier, 'space_code' => $sensor->espacioActual->recurso->codigo, 'type' => 'PRESENCIA', 'value' => null, 'occurred_at' => now()->startOfSecond()->toIso8601String()], JSON_THROW_ON_ERROR);
    $stamp = (string) now()->timestamp;
    $key = hash_hmac('sha256', 'sensor:'.$identifier, hex2bin($master), true);
    $signature = hash_hmac('sha256', "POST\n/api/iot/events\n".$identifier."\n".$stamp."\n".hash('sha256', $body), $key);
    $curl = curl_init('http://127.0.0.1:8000/api/iot/events');
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'X-IoT-Sensor: '.$identifier, 'X-IoT-Timestamp: '.$stamp, 'X-IoT-Signature: '.$signature], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $data = is_string($response) ? json_decode($response, true) : null;
    $allowed = ['accepted', 'duplicate', 'authentication_failed', 'device_not_authorized', 'invalid_json', 'invalid_payload', 'space_context_mismatch', 'rate_limited', 'temporarily_unavailable'];
    echo json_encode(['status' => $status, 'result' => in_array($data['result'] ?? null, $allowed, true) ? $data['result'] : 'unavailable', 'event_id' => is_int($data['event_id'] ?? null) ? $data['event_id'] : null]).PHP_EOL;
    exit(in_array($status, [200, 201]) ? 0 : 1);
} catch (Throwable) {
    fwrite(STDERR, 'No se pudo ejecutar el simulador. Compruebe el entorno local, sensor, configuración privada y servidor.'.PHP_EOL);
    exit(1);
}
