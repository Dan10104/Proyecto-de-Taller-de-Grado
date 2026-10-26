<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationReconciler;
use App\Services\Notifications\NotificationWindow;
use Illuminate\Console\Command;

class ReconcileNotifications extends Command
{
    protected $signature = 'notificaciones:reconciliar';

    protected $description = 'Recuperar avisos internos elegibles de las últimas 24 horas.';

    public function handle(NotificationReconciler $reconciler, NotificationWindow $window): int
    {
        if ($window->start() === null) {
            $this->error('RF21 requiere configurar su fecha inicial de habilitación.');

            return self::FAILURE;
        }
        $counts = $reconciler->run();
        $this->info('Explorados: '.$counts['explorados'].'; creados: '.$counts['creados'].'; pendientes: '.$counts['fallidos'].'.');

        return $counts['fallidos'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
