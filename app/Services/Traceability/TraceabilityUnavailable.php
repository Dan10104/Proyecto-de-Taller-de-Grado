<?php

namespace App\Services\Traceability;

final class TraceabilityUnavailable extends \RuntimeException
{
    public function __construct(public readonly string $reason = 'NO_VERIFICABLE')
    {
        parent::__construct('No fue posible comprobar la evidencia.');
    }
}
