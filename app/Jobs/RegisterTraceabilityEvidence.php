<?php

namespace App\Jobs;

use App\Services\Traceability\EvidenceProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class RegisterTraceabilityEvidence implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(public readonly int $evidenceId) {}

    public function backoff(): array
    {
        return [60, 120, 300, 900, 3600];
    }

    public function handle(EvidenceProcessor $processor): void
    {
        if (! config('blockchain.enabled')) {
            return;
        }
        $key = 'traceability:retry:'.$this->evidenceId;
        $attempt = Cache::get($key, ['count' => 0, 'after' => 0]);
        if ($attempt['after'] > time()) {
            return;
        }
        try {
            $processor->register($this->evidenceId);
        } catch (\Throwable) {
            Log::warning('traceability.external_pending', ['evidence_id' => $this->evidenceId]);
        } finally {
            // Cache is only a throttle. Durable recovery always scans activity/evidence rows.
            $count = min(4, $attempt['count'] + 1);
            Cache::put($key, ['count' => $count, 'after' => time() + $this->backoff()[$count]], 7200);
        }
    }
}
