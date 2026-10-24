<?php

namespace App\Exceptions;

use RuntimeException;

class IotException extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $result, public readonly ?int $retryAfter = null)
    {
        parent::__construct($result);
    }

    public function response()
    {
        return response()->json(['result' => $this->result], $this->status, array_filter([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Retry-After' => $this->retryAfter,
        ], fn ($value) => $value !== null));
    }
}
