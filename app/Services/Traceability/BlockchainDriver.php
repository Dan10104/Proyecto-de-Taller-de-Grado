<?php

namespace App\Services\Traceability;

interface BlockchainDriver
{
    public function chainId(): int;

    public function isRegistered(string $hash): bool;

    public function register(string $hash): array;

    public function registrationTimestamp(string $hash): ?int;

    public function find(string $hash, ?string $reference = null): array;
}
