<?php

namespace Tests\Support;

use App\Services\Traceability\BlockchainDriver;
use App\Services\Traceability\TraceabilityUnavailable;

final class FakeBlockchainDriver implements BlockchainDriver
{
    public int $chain = 11155111;

    public int $calls = 0;

    public int $broadcasts = 0;

    public bool $unavailable = false;

    public array $proofs = [];

    public ?\Closure $onFind = null;

    public ?\Closure $onRegister = null;

    public function chainId(): int
    {
        $this->check();

        return $this->chain;
    }

    public function isRegistered(string $hash): bool
    {
        $this->check();

        return ($this->proofs[$hash]['status'] ?? '') === 'CONFIRMED';
    }

    public function registrationTimestamp(string $hash): ?int
    {
        $this->check();

        return $this->proofs[$hash]['timestamp'] ?? null;
    }

    public function register(string $hash): array
    {
        if ($this->onRegister) {
            ($this->onRegister)();
        }
        $this->check();
        $this->broadcasts++;

        return $this->proofs[$hash] = ['status' => 'CONFIRMED', 'reference' => 'eip155:11155111/'.config('blockchain.contract_address').'/0x'.hash('sha256', $hash), 'timestamp' => 1792960000];
    }

    public function find(string $hash, ?string $reference = null): array
    {
        $this->check();
        if ($this->onFind) {
            ($this->onFind)();
        }

        return $this->proofs[$hash] ?? ['status' => $reference ? 'PENDING' : 'ABSENT'];
    }

    private function check(): void
    {
        $this->calls++;
        if ($this->unavailable) {
            throw new TraceabilityUnavailable('FAKE_UNAVAILABLE');
        }
    }
}
