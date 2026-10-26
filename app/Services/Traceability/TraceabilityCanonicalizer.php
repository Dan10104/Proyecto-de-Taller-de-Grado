<?php

namespace App\Services\Traceability;

use Symfony\Component\Process\Process;

final class TraceabilityCanonicalizer
{
    public function serialize(array $value): string
    {
        // Explicitly remove inherited environment, particularly RPC/key and DB credentials.
        $environment = ProcessEnvironment::isolated();
        $process = new Process([config('blockchain.node'), base_path('blockchain/runtime/jcs.mjs')], base_path('blockchain'), $environment);
        $process->setTimeout(3);
        try {
            $process->setInput(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
            $process->run();
            if (! $process->isSuccessful() || $process->getOutput() === '') {
                throw new TraceabilityUnavailable('JCS_REJECTED');
            }

            return $process->getOutput();
        } catch (\Throwable) {
            // Never include input, subprocess command/environment or provider errors.
            throw new TraceabilityUnavailable('JCS_REJECTED');
        }
    }
}
