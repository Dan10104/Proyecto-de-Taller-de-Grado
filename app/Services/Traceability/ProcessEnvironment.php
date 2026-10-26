<?php

namespace App\Services\Traceability;

final class ProcessEnvironment
{
    public static function isolated(): array
    {
        $all = array_merge(getenv(), $_SERVER, $_ENV);
        $environment = array_fill_keys(array_keys($all), false);
        foreach ($all as $key => $value) {
            if (in_array(strtoupper((string) $key), ['SYSTEMROOT', 'WINDIR', 'PATH', 'PATHEXT', 'TEMP', 'TMP'], true) && is_string($value)) {
                $environment[$key] = $value;
            }
        }

        return $environment;
    }
}
