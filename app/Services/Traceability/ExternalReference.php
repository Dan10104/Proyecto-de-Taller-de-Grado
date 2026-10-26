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

    public static function explorer(?string $reference): ?string
    {
        $parsed = self::parse($reference);

        return $parsed ? 'https://sepolia.etherscan.io/tx/'.$parsed['transaction'] : null;
    }
}
