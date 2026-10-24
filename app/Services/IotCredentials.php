<?php

namespace App\Services;

use App\Exceptions\IotException;

class IotCredentials
{
    public function key(string $identifier): string
    {
        $master = config('iot.master_secret');
        if (! is_string($master) || ! preg_match('/\A[0-9a-f]{64}\z/D', $master)) {
            throw new IotException(503, 'temporarily_unavailable');
        }

        return hash_hmac('sha256', 'sensor:'.$identifier, hex2bin($master), true);
    }

    public function canonical(string $identifier, string $timestamp, #[\SensitiveParameter] string $raw): string
    {
        return "POST\n/api/iot/events\n".$identifier."\n".$timestamp."\n".hash('sha256', $raw);
    }

    public function checkClock(string $timestamp): void
    {
        if (abs(now()->timestamp - (int) $timestamp) > config('iot.request_max_skew_seconds')) {
            throw new IotException(401, 'authentication_failed');
        }
    }
}
