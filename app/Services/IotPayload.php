<?php

namespace App\Services;

use App\Enums\TipoEventoOcupacion;
use App\Exceptions\IotException;
use Carbon\CarbonImmutable;
use JsonException;
use stdClass;

class IotPayload
{
    public function parse(#[\SensitiveParameter] string $raw, string $identifier): array
    {
        try {
            $object = json_decode($raw, false, 16, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException) {
            throw new IotException(400, 'invalid_json');
        }
        if (! $object instanceof stdClass) {
            throw new IotException(422, 'invalid_payload');
        }
        foreach ((array) $object as $value) {
            if (is_object($value) || is_array($value)) {
                throw new IotException(422, 'invalid_payload');
            }
        }
        // JSON validity was checked above. Walk the flat object to retain numeric
        // lexemes and detect duplicate names, including escaped equivalent names.
        $tokens = [];
        $i = strpos($raw, '{') + 1;
        $length = strlen($raw);
        while ($i < $length) {
            $this->whitespace($raw, $i);
            if ($raw[$i] === '}') {
                break;
            }
            $key = json_decode($this->quoted($raw, $i), true, 16, JSON_THROW_ON_ERROR);
            if (array_key_exists($key, $tokens)) {
                throw new IotException(400, 'invalid_json');
            }
            $this->whitespace($raw, $i);
            $i++; // colon (syntax already validated)
            $this->whitespace($raw, $i);
            if ($raw[$i] === '"') {
                $token = $this->quoted($raw, $i);
            } else {
                $start = $i;
                while ($i < $length && ! str_contains(",} \t\r\n", $raw[$i])) {
                    $i++;
                }
                $token = substr($raw, $start, $i - $start);
            }
            $tokens[$key] = $token;
            $this->whitespace($raw, $i);
            if ($raw[$i++] === '}') {
                break;
            }
        }
        $data = (array) $object;
        if (array_diff(array_keys($data), ['sensor_identifier', 'space_code', 'type', 'occurred_at', 'value'])) {
            throw new IotException(422, 'invalid_payload');
        }
        foreach (['sensor_identifier', 'space_code', 'type', 'occurred_at'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field])) {
                throw new IotException(422, 'invalid_payload');
            }
        }
        if ($data['sensor_identifier'] !== $identifier || ! preg_match('/\A[\x00-\x7f]{1,60}\z/D', $data['space_code']) || TipoEventoOcupacion::tryFrom($data['type']) === null) {
            throw new IotException(422, 'invalid_payload');
        }
        $date = $this->date($data['occurred_at']);
        $this->checkEventClock($date);

        return ['space_code' => $data['space_code'], 'type' => $data['type'], 'value' => $this->decimal($tokens['value'] ?? 'null'), 'occurred_at' => $date];
    }

    private function whitespace(string $raw, int &$i): void
    {
        while (isset($raw[$i]) && str_contains(" \t\r\n", $raw[$i])) {
            $i++;
        }
    }

    private function quoted(string $raw, int &$i): string
    {
        $start = $i++;
        while (isset($raw[$i])) {
            $char = $raw[$i++];
            if ($char === '\\') {
                $i++;
            } elseif ($char === '"') {
                break;
            }
        }

        return substr($raw, $start, $i - $start);
    }

    public function decimal(string $token): ?string
    {
        if ($token === 'null') {
            return null;
        }
        if (! preg_match('/\A(-?)(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?\z/D', $token, $parts)) {
            throw new IotException(422, 'invalid_payload');
        }
        $fraction = str_pad($parts[3] ?? '', 2, '0');
        $sign = $parts[2] === '0' && $fraction === '00' ? '' : $parts[1];

        return $sign.$parts[2].'.'.$fraction;
    }

    public function date(string $text): CarbonImmutable
    {
        if (! preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[+-][0-9]{2}:[0-9]{2})\z/D', $text, $m)
            || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || (int) $m[4] > 23 || (int) $m[5] > 59 || (int) $m[6] > 59
            || $m[7] === '-00:00' || ($m[7] !== 'Z' && ((int) substr($m[7], 1, 2) > 23 || (int) substr($m[7], 4, 2) > 59))) {
            throw new IotException(422, 'invalid_payload');
        }
        $date = CarbonImmutable::createFromFormat('!Y-m-d\TH:i:sP', str_ends_with($text, 'Z') ? substr($text, 0, -1).'+00:00' : $text)->setTimezone('America/La_Paz');
        if ($date->year < 1000 || $date->year > 9999) {
            throw new IotException(422, 'invalid_payload');
        }

        return $date;
    }

    public function checkEventClock(CarbonImmutable $date): void
    {
        if ($date->timestamp > now()->timestamp + config('iot.request_max_skew_seconds')) {
            throw new IotException(422, 'invalid_payload');
        }
    }
}
