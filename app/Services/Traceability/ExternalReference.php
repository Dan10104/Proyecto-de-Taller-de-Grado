<?php

namespace App\Services\Traceability;

final class ExternalReference
{
    public static function parse(?string $reference): ?array
    {
        if (! is_string($reference) || ! preg_match('~^eip155:11155111/(0x[0-9a-f]{40})/(0x[0-9a-f]{64})$~D', $reference, $match)) {
            return null;
        }

        return ['contract' => $match[1], 'transaction' => $match[2]];
    }

    /**
     * Link to the public explorer, only for a reference that is known to live on the public testnet: the public
     * environment is configured, the reference belongs to the contract configured now, and that contract was
     * never a deployment of the local simulation. Anything else gets no public link.
     */
    public static function explorer(?string $reference): ?string
    {
        $network = self::network($reference);
        if ($network['transaccion'] === null || $network['simulada'] || $network['otro_despliegue'] || config('blockchain.environment', 'public') !== 'public') {
            return null;
        }

        return 'https://sepolia.etherscan.io/tx/'.$network['transaccion'];
    }

    /**
     * Where a reference lives, for display. Its origin does not change when the configuration does:
     * - a reference to a contract deployed on the local simulation is always a local record;
     * - a reference to a contract other than the configured one belongs to another deployment and is never
     *   presented as the current network.
     * Without a reference, the answer describes the configured environment.
     */
    public static function network(?string $reference): array
    {
        $parsed = self::parse($reference);
        $configured = strtolower((string) config('blockchain.contract_address', ''));
        $local = config('blockchain.environment', 'public') === 'local-simulation';
        $current = $parsed === null || $parsed['contract'] === $configured;

        return [
            'simulada' => $parsed === null ? $local : (in_array($parsed['contract'], (array) config('blockchain.simulation_contracts', []), true) || ($local && $current)),
            'otro_despliegue' => ! $current,
            'contrato' => $parsed['contract'] ?? null,
            'transaccion' => $parsed['transaction'] ?? null,
        ];
    }
}
