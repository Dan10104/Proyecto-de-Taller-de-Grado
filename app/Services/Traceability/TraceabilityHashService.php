<?php

namespace App\Services\Traceability;

final class TraceabilityHashService
{
    public function digest(string $bytes): string
    {
        return hash('sha256', $bytes);
    }
}
