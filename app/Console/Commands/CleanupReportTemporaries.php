<?php

namespace App\Console\Commands;

use App\Services\Reporting\ReportTemporaryStore;
use Illuminate\Console\Command;

class CleanupReportTemporaries extends Command
{
    protected $signature = 'reportes:limpiar-temporales';

    protected $description = 'Eliminar temporales privados huérfanos de reportes, sin tocar exportaciones activas';

    public function handle(ReportTemporaryStore $store): int
    {
        $this->info('Directorios temporales retirados: '.$store->cleanup());

        return self::SUCCESS;
    }
}
