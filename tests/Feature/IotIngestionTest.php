<?php

namespace Tests\Feature;

use App\Exceptions\IotException;
use App\Models\EspacioAcademico;
use App\Models\EventoOcupacion;
use App\Models\SensorIot;
use App\Services\IotCredentials;
use App\Services\IotManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\IotTestCase;

class IotIngestionTest extends IotTestCase
{
    public function test_valid_raw_signature_persists_server_context_and_default_receipt_atomically(): void
    {
        $s = SensorIot::factory()->create();
        $this->signed($s, $this->body($s, ['value' => 1]))->assertCreated()->assertJsonPath('result', 'accepted');
        $e = EventoOcupacion::sole();
        $this->assertSame($s->id, $e->sensor_id);
        $this->assertSame($s->espacio_actual_id, $e->espacio_historico_id);
        $this->assertSame('1.00', $e->valor);
        $this->assertTrue($e->fecha_recepcion->eq($s->fresh()->ultima_comunicacion));
        $this->assertSame('-04:00', DB::selectOne('SELECT @@session.time_zone as z')->z);
    }

    public function test_signature_is_raw_and_unknown_invalid_or_disabled_are_safe(): void
    {
        $s = SensorIot::factory()->create();
        $this->signed($s, '{invalid', ['X-IoT-Signature' => str_repeat('0', 64)])->assertStatus(401)->assertExactJson(['result' => 'authentication_failed']);
        $unknown = clone $s;
        $unknown->identificador = 'SYN-UNKNOWN';
        $this->signed($unknown, $this->body($unknown))->assertStatus(401)->assertExactJson(['result' => 'authentication_failed']);
        $s->forceFill(['estado' => 'DESHABILITADO'])->save();
        $this->signed($s, $this->body($s))->assertStatus(403)->assertExactJson(['result' => 'device_not_authorized']);
        $this->signed($s, $this->body($s), ['X-IoT-Signature' => str_repeat('0', 64)])->assertStatus(401);
        $this->assertNull($s->fresh()->ultima_comunicacion);
        $this->assertDatabaseCount('eventos_ocupacion', 0);
    }

    public function test_signature_mutations_and_path_method_query_are_rejected(): void
    {
        $s = SensorIot::factory()->create();
        $raw = $this->body($s);
        $key = app(IotCredentials::class)->key($s->identificador);
        $signature = hash_hmac('sha256', app(IotCredentials::class)->canonical($s->identificador, (string) now()->timestamp, $raw), $key);
        foreach ([' '.$raw, str_replace('PRESENCIA', 'OCUPACION', $raw), substr($raw, 0, -1).',"value":0}'] as $changed) {
            $this->signed($s, $changed, ['X-IoT-Signature' => $signature])->assertStatus(401);
        }
        foreach (['/api/iot/events/', '/api/iot/events?x=1', '/api/iot/other'] as $path) {
            $this->signed($s, $raw, [], $path)->assertNotFound();
        }
        foreach (['GET', 'PUT', 'DELETE'] as $method) {
            $this->signed($s, $raw, [], '/api/iot/events', $method)->assertStatus(405);
        }
        $this->signed($s, $raw, ['X-IoT-Signature' => strtoupper($signature)])->assertStatus(401);
    }

    public function test_timestamp_boundaries_malformed_and_duplicate_headers(): void
    {
        $this->freezeSecond();
        $s = SensorIot::factory()->create();
        foreach ([-301 => 401, -300 => 201, 300 => 200, 301 => 401] as $offset => $status) {
            $this->signed($s, $this->body($s), ['X-IoT-Timestamp' => (string) (now()->timestamp + $offset)])->assertStatus($status);
        }
        foreach (['0', '01', '-1', '+1', '1.0', '9999999999999', ' 1', '1 ', '1e9', ['1', '2']] as $stamp) {
            $this->signed($s, $this->body($s), ['X-IoT-Timestamp' => $stamp])->assertStatus(401);
        }
        foreach (['X-IoT-Sensor' => [$s->identificador, $s->identificador], 'X-IoT-Signature' => [str_repeat('0', 64), str_repeat('0', 64)]] as $name => $value) {
            $this->signed($s, $this->body($s), [$name => $value])->assertStatus(401);
        }
    }

    public function test_authenticated_json_is_strict_including_escaped_duplicate_keys(): void
    {
        $s = SensorIot::factory()->create();
        $raw = $this->body($s);
        foreach (['{', "\xef\xbb\xbf".$raw, $raw.'{}', str_replace('"type":', '"type":"OCUPACION","t\\u0079pe":', $raw)] as $body) {
            $this->signed($s, $body)->assertStatus(400)->assertExactJson(['result' => 'invalid_json']);
        }
        foreach (['[]', '{}', 'null', '{"sensor_identifier":{}}', $this->body($s, ['email' => 'synthetic@example.test']), $this->body($s, ['sensor_id' => $s->id]), $this->body($s, ['sensor_identifier' => 'OTHER'])] as $body) {
            $this->signed($s, $body)->assertStatus(422)->assertExactJson(['result' => 'invalid_payload']);
        }
        $this->assertDatabaseCount('eventos_ocupacion', 0);
        $this->assertNull($s->fresh()->ultima_comunicacion);
    }

    public function test_payload_identity_type_and_context_are_exact(): void
    {
        $s = SensorIot::factory()->create();
        foreach (['sensor_identifier' => strtolower($s->identificador), 'type' => 'ONLINE', 'space_code' => $s->espacio_actual_id] as $field => $value) {
            $this->signed($s, $this->body($s, [$field => $value]))->assertStatus(422);
        }
        $this->signed($s, $this->body($s, ['space_code' => 'OTHER']))->assertStatus(409)->assertExactJson(['result' => 'space_context_mismatch']);
        $this->signed($s, $this->body($s, ['type' => 'OCUPACION']))->assertCreated();
        $this->assertSame('OCUPACION', EventoOcupacion::sole()->tipo->value);
    }

    public function test_decimal_lexemes_preserve_precision_and_reject_coercion(): void
    {
        $s = SensorIot::factory()->create();
        $raw = substr($this->body($s), 0, -1);
        foreach (['-99999999.99' => '-99999999.99', '99999999.99' => '99999999.99', '-0' => '0.00', '1.20' => '1.20'] as $token => $expected) {
            $this->signed($s, $raw.',"value":'.$token.'}')->assertCreated();
            $this->assertSame($expected, EventoOcupacion::latest('id')->first()->valor);
        }
        foreach (['100000000', '-100000000', '1.001', '1e0', 'true', 'false', '"1"', '[]', '{}'] as $token) {
            $this->signed($s, $raw.',"value":'.$token.'}')->assertStatus(422);
        }
        $this->signed($s, $raw.',"value":NaN}')->assertStatus(400);
        $this->assertDatabaseCount('eventos_ocupacion', 4);
    }

    public function test_semantic_duplicates_keep_original_receipt_and_communication(): void
    {
        $this->freezeSecond();
        $s = SensorIot::factory()->create();
        $raw = substr($this->body($s), 0, -1);
        $id = $this->signed($s, $raw.',"value":1}')->assertCreated()->json('event_id');
        $receipt = $s->fresh()->ultima_comunicacion;
        $this->travel(10)->seconds();
        foreach (['1.0', '1.00'] as $value) {
            $this->signed($s, ' '.$raw.', "value": '.$value.' }')->assertOk()->assertJsonPath('event_id', $id)->assertJsonPath('result', 'duplicate');
        }
        $this->assertTrue($receipt->eq($s->fresh()->ultima_comunicacion));
        $null = $this->signed($s, $raw.'}')->assertCreated()->json('event_id');
        $this->signed($s, $raw.',"value":null}')->assertOk()->assertJsonPath('event_id', $null);
        $this->signed($s, $raw.',"value":0}')->assertCreated();
        $this->signed($s, $raw.',"value":-0.0}')->assertOk();
        $this->assertDatabaseCount('eventos_ocupacion', 3);
    }

    public function test_dates_normalize_offsets_and_reject_invalid_calendar_precision_and_ranges(): void
    {
        $s = SensorIot::factory()->create();
        foreach (['2026-01-02T12:00:00Z', '2026-01-02T08:00:00-04:00', '2026-01-02T13:00:00+01:00'] as $i => $date) {
            $this->signed($s, $this->body($s, ['occurred_at' => $date]))->assertStatus($i === 0 ? 201 : 200);
        }
        $this->assertSame('2026-01-02 08:00:00', EventoOcupacion::sole()->fecha_evento->format('Y-m-d H:i:s'));
        foreach (['2026-02-30T12:00:00Z', '2026-01-01T12:00:00', '2026-01-01T12:00:00.1Z', '2026-01-01T12:00:60Z', '2026-01-01T12:00:00-00:00', '2026-01-01T24:00:00Z', '2026-01-01T12:00:00+24:00', '1000-01-01T00:00:00Z', '0000-01-01T00:00:00Z'] as $date) {
            $this->signed($s, $this->body($s, ['occurred_at' => $date]))->assertStatus(422);
        }
    }

    public function test_future_boundary_history_and_forged_receipt(): void
    {
        $this->freezeSecond();
        $s = SensorIot::factory()->create();
        $this->signed($s, $this->body($s, ['occurred_at' => now()->addSeconds(300)->toIso8601String()]))->assertCreated();
        $this->signed($s, $this->body($s, ['occurred_at' => now()->addSeconds(301)->toIso8601String()]))->assertStatus(422);
        $this->signed($s, $this->body($s, ['fecha_recepcion' => now()->toIso8601String()]))->assertStatus(422);
        $this->signed($s, $this->body($s, ['occurred_at' => '1000-01-02T00:00:00Z']))->assertCreated();
        $this->assertTrue($s->fresh()->ultima_comunicacion->isToday());
    }

    public function test_reassignment_preserves_history_and_rejects_old_context_even_on_replay(): void
    {
        $s = SensorIot::factory()->create();
        $oldBody = $this->body($s);
        $oldSpace = $s->espacio_actual_id;
        $this->signed($s, $oldBody)->assertCreated();
        $b = EspacioAcademico::factory()->create();
        app(IotManagementService::class)->save($this->user('Administrador'), $s, ['espacio_actual_id' => $b->recurso_id, 'tipo' => 'Sintético']);
        $this->signed($s, $oldBody)->assertStatus(409);
        $this->signed($s->fresh(), $this->body($s->fresh()))->assertCreated();
        $this->assertSame([$oldSpace, $b->recurso_id], EventoOcupacion::orderBy('id')->pluck('espacio_historico_id')->all());
    }

    public function test_transport_body_limits_content_type_encoding_and_missing_length(): void
    {
        $s = SensorIot::factory()->create();
        $raw = $this->body($s);
        $this->signed($s, str_pad($raw, 4096))->assertCreated();
        $this->signed($s, str_pad($raw, 4097))->assertStatus(413);
        foreach (['text/plain', 'application/json; charset=ascii', 'application/json; charset=utf-8; x=1', ['application/json', 'application/json']] as $type) {
            $this->signed($s, $raw, ['Content-Type' => $type])->assertStatus(415);
        }
        $this->signed($s, $raw, ['Content-Type' => 'application/json; charset=utf-8'])->assertOk();
        $this->signed($s, $raw, ['Content-Encoding' => 'gzip'])->assertStatus(415);
        $this->app['env'] = 'production';
        $this->signed($s, $raw)->assertStatus(403)->assertJsonPath('result', 'secure_transport_required');
    }

    public function test_quota_120_121_invalid_signatures_do_not_consume_victim_quota_and_store_failure_closes(): void
    {
        config(['iot.rate_limit_per_minute' => 120]);
        $s = SensorIot::factory()->create();
        $raw = $this->body($s);
        for ($i = 0; $i < 125; $i++) {
            $this->signed($s, $raw, ['X-IoT-Signature' => str_repeat('0', 64)])->assertStatus(401);
        }
        for ($i = 0; $i < 120; $i++) {
            $this->signed($s, $raw)->assertStatus($i === 0 ? 201 : 200);
        }
        $this->signed($s, $raw)->assertStatus(429)->assertHeader('Retry-After');
        $other = SensorIot::factory()->create();
        $this->signed($other, $this->body($other))->assertCreated();
        config(['iot.rate_store' => 'array']);
        $this->signed($s, $raw)->assertStatus(503)->assertExactJson(['result' => 'temporarily_unavailable']);
    }

    public function test_secrets_are_environment_and_sensor_specific_master_failure_is_closed(): void
    {
        $s = SensorIot::factory()->create();
        $credentials = app(IotCredentials::class);
        $key = $credentials->key($s->identificador);
        $this->assertSame(32, strlen($key));
        $this->assertFalse(hash_equals($key, $credentials->key('ANOTHER-SYNTHETIC')));
        config(['iot.master_secret' => bin2hex(random_bytes(32))]);
        $this->assertFalse(hash_equals($key, $credentials->key($s->identificador)));
        foreach ([null, '', 'bad', str_repeat('g', 64)] as $master) {
            config(['iot.master_secret' => $master]);
            try {
                $credentials->key($s->identificador);
                $this->fail('La configuración inválida debe rechazar la operación.');
            } catch (IotException $error) {
                $this->assertSame(503, $error->status);
            }
        }
    }

    public function test_safe_responses_and_logs_do_not_echo_request_or_credentials(): void
    {
        Log::spy();
        $s = SensorIot::factory()->create();
        $r = $this->signed($s, $this->body($s, ['personal' => 'synthetic@example.test']));
        $r->assertStatus(422);
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertFalse(str_contains($r->getContent(), $s->identificador));
        Log::shouldHaveReceived('info')->with('Recepción IoT.', ['status' => 422])->once();
        Log::shouldNotHaveReceived('error');
    }
}
