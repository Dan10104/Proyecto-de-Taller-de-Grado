<?php

namespace App\Console\Commands;

use App\Services\ReleaseScanService;
use Illuminate\Console\Command;

class EvaluateReleasePolicies extends Command
{
    protected $signature = 'reservas:evaluar-liberacion';

    protected $description = 'Evalúa políticas de liberación con evidencia persistida y guardas D-08.';

    public function handle(ReleaseScanService $scanner): int
    {
        $counts = $scanner->run();
        foreach ($counts as $label => $value) {
            $this->line(ucfirst($label).': '.$value);
        }

        return $counts['errores'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
