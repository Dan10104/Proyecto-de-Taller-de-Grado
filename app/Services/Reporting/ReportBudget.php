<?php

namespace App\Services\Reporting;

use Illuminate\Validation\ValidationException;

class ReportBudget
{
    private float $started;

    public function __construct()
    {
        $this->started = microtime(true);
    }

    public function check(?string $file = null): void
    {
        if (microtime(true) - $this->started > max(1, (int) config('reporting.timeout_seconds'))) {
            $this->fail('La exportación superó el tiempo disponible. Refine los filtros.');
        }
        if (memory_get_usage(true) > max(1, (int) config('reporting.memory_bytes'))) {
            $this->fail('La exportación superó el presupuesto de memoria. Refine los filtros.');
        }
        if ($file !== null && is_file($file)) {
            clearstatcache(true, $file);
            if (filesize($file) > max(1, (int) config('reporting.max_bytes'))) {
                $this->fail('El archivo superó el tamaño permitido. Refine los filtros.');
            }
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['exportacion' => $message]);
    }
}
