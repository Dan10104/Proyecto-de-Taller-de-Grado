<?php

namespace App\Services\Traceability;

use Illuminate\Support\Facades\Cache;
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
        $proof = $this->call('find', $hash, $reference);
        if (($proof['status'] ?? null) === 'CONFIRMED' && is_int($proof['block'] ?? null) && is_int($proof['confirmations'] ?? null) && is_string($proof['reference'] ?? null)) {
            // Public facts of the last confirmation seen, kept for display only; never used as proof. They belong
            // to one transaction on one environment, and losing them never affects the confirmation itself.
            try {
                Cache::put(self::proofKey($proof['reference']), ['block' => $proof['block'], 'confirmations' => $proof['confirmations'], 'seen_at' => now()->toIso8601String()], now()->addDays(30));
            } catch (\Throwable) {
                // display metadata only
            }
        }

        return $proof;
    }

    public static function proofKey(string $reference): string
    {
        return 'traceability:proof:'.hash('sha256', config('blockchain.environment').'|'.$reference);
    }

    /** Block and confirmations last seen for this exact reference on the configured environment, if any. */
    public static function lastSeen(?string $reference): ?array
    {
        if ($reference === null) {
            return null;
        }
        try {
            $seen = Cache::get(self::proofKey($reference));
        } catch (\Throwable) {
            return null;
        }

        return is_array($seen) && is_int($seen['block'] ?? null) && is_int($seen['confirmations'] ?? null)
            ? ['bloque' => $seen['block'], 'confirmaciones' => $seen['confirmations'], 'consultada_en' => $seen['seen_at'] ?? null] : null;
    }

    public static function simulated(): bool
    {
        return config('blockchain.environment') === 'local-simulation';
    }

    private function call(string $action, ?string $hash = null, ?string $reference = null): array
    {
        if (! config('blockchain.enabled') || config('blockchain.chain_id') !== 11155111 || config('blockchain.network') !== 'sepolia') {
            throw new TraceabilityUnavailable('DISABLED_OR_WRONG_CHAIN');
        }
        // The environment is explicit: an unknown value, or the local simulation in production, is never
        // treated as the public testnet. The Node bridge enforces the matching transport and network identity.
        if (! in_array(config('blockchain.environment'), ['public', 'local-simulation'], true) || (self::simulated() && app()->environment('production'))) {
            throw new TraceabilityUnavailable('WRONG_ENVIRONMENT');
        }
        $environment = ProcessEnvironment::isolated();
        foreach (['RPC_URL' => 'rpc_url', 'PRIVATE_KEY' => 'private_key', 'CONTRACT_ADDRESS' => 'contract_address', 'CHAIN_ID' => 'chain_id', 'CONFIRMATIONS' => 'confirmations', 'DEPLOYMENT_BLOCK' => 'deployment_block', 'ENVIRONMENT' => 'environment'] as $key => $config) {
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
