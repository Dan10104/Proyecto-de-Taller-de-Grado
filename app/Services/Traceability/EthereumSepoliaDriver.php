<?php

namespace App\Services\Traceability;

use Symfony\Component\Process\Process;

final class EthereumSepoliaDriver implements BlockchainDriver
{
    public function chainId(): int
    {
        return (int) $this->call('chain')['chainId'];
    }

    public function isRegistered(string $hash): bool
    {
        return (bool) $this->call('exists', $hash)['registered'];
    }

    public function register(string $hash): array
    {
        return $this->call('register', $hash);
    }

    public function registrationTimestamp(string $hash): ?int
    {
        return $this->call('timestamp', $hash)['timestamp'] ?? null;
    }

    public function find(string $hash, ?string $reference = null): array
    {
        return $this->call('find', $hash, $reference);
    }

    private function call(string $action, ?string $hash = null, ?string $reference = null): array
    {
        if (! config('blockchain.enabled') || config('blockchain.chain_id') !== 11155111 || config('blockchain.network') !== 'sepolia') {
            throw new TraceabilityUnavailable('DISABLED_OR_WRONG_CHAIN');
        }
        $environment = ProcessEnvironment::isolated();
        foreach (['RPC_URL' => 'rpc_url', 'PRIVATE_KEY' => 'private_key', 'CONTRACT_ADDRESS' => 'contract_address', 'CHAIN_ID' => 'chain_id', 'CONFIRMATIONS' => 'confirmations', 'DEPLOYMENT_BLOCK' => 'deployment_block'] as $key => $config) {
            $environment['BLOCKCHAIN_'.$key] = (string) config('blockchain.'.$config);
        }
        $process = new Process([config('blockchain.node'), base_path('blockchain/runtime/traceability.mjs')], base_path('blockchain'), $environment);
        $process->setTimeout(config('blockchain.timeout'));
        $process->setInput(json_encode(['action' => $action, 'hash' => $hash, 'reference' => $reference], JSON_THROW_ON_ERROR));
        try {
            $process->run();
            $lines = array_values(array_filter(explode("\n", trim($process->getOutput()))));
            $result = json_decode(end($lines) ?: '{}', true, 32, JSON_THROW_ON_ERROR);
            if (! $process->isSuccessful() || ! is_array($result) || ($result['ok'] ?? false) !== true) {
                throw new TraceabilityUnavailable;
            }

            return $result;
        } catch (\Throwable) {
            if ($action === 'register') {
                // Only public, validated pre-broadcast candidate survives a timeout; never parse diagnostics.
                foreach (explode("\n", $process->getOutput()) as $line) {
                    $candidate = json_decode($line, true);
                    if (is_array($candidate) && ($candidate['ok'] ?? false) === true && ($candidate['status'] ?? '') === 'PENDING' && ExternalReference::parse($candidate['reference'] ?? null)) {
                        return $candidate;
                    }
                }
            }
            throw new TraceabilityUnavailable('EXTERNAL_UNAVAILABLE');
        }
    }
}
