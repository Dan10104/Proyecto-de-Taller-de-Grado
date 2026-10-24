<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class QrTokenService
{
    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public function hash(#[\SensitiveParameter] string $payload): string
    {
        return hash('sha256', $payload);
    }

    public function canonical(#[\SensitiveParameter] string $payload): bool
    {
        if (! preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $payload)) {
            return false;
        }
        $bytes = base64_decode(strtr($payload, '-_', '+/').'=', true);

        return $bytes !== false && strlen($bytes) === 32
            && rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=') === $payload;
    }

    public function admit(#[\SensitiveParameter] mixed $payload): string
    {
        if (! is_string($payload) || strlen($payload) < 1 || strlen($payload) > 1024 || ! mb_check_encoding($payload, 'UTF-8')) {
            throw ValidationException::withMessages(['qr_token' => 'Se requiere una lectura QR de entre 1 y 1024 bytes.']);
        }

        return $payload;
    }
}
