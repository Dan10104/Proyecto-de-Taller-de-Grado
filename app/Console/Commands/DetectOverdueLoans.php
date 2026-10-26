<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationWindow;
use App\Services\Notifications\OverdueLoanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetectOverdueLoans extends Command
{
    protected $signature = 'prestamos:detectar-vencimientos';

    protected $description = 'Registrar una detección de vencimiento por préstamo entregado.';

    public function handle(OverdueLoanService $detector, NotificationWindow $window): int
    {
        if ($window->start() === null || $window->start()->isFuture()) {
            $this->error('RF21 aún no está habilitado.');

            return self::FAILURE;
        }
        $created = $failed = 0;
        DB::table('prestamos')->where('estado', 'ENTREGADO')->where('fin_previsto', '<', now())
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('registros_actividad')->whereColumn('prestamo_id', 'prestamos.id')->where('accion', 'PRESTAMO_VENCIDO'))
            ->chunkById(100, function ($rows) use ($detector, &$created, &$failed) {
                foreach ($rows as $row) {
                    try {
                        $created += (int) $detector->detect((int) $row->id);
                    } catch (\Throwable) {
                        $failed++;
                        Log::warning('notifications.overdue_pending', ['loan_id' => $row->id]);
                    }
                }
            });
        $this->info('Vencimientos registrados: '.$created.'; pendientes: '.$failed.'.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
