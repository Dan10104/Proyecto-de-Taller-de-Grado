<?php

namespace App\Services\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationReconciler
{
    public function __construct(private NotificationWindow $window, private NotificationGenerationService $generator) {}

    public function run(): array
    {
        $counts = ['explorados' => 0, 'creados' => 0, 'fallidos' => 0];
        $start = $this->window->start();
        if ($start === null) {
            return $counts;
        }
        $now = CarbonImmutable::now('America/La_Paz')->startOfSecond();
        $lower = $start->max($now->subHours(24));
        DB::table('registros_actividad')->where('resultado', 'EXITO')->whereIn('accion', array_keys(NotificationRuleRegistry::RULES))
            ->whereBetween('fecha_hora', [$lower->toDateTimeString(), $now->toDateTimeString()])->chunkById(100, function ($rows) use (&$counts) {
                foreach ($rows as $row) {
                    $counts['explorados']++;
                    try {
                        $counts['creados'] += $this->generator->generate((int) $row->id);
                    } catch (\Throwable) {
                        $counts['fallidos']++;
                        Log::warning('notifications.reconcile_pending', ['activity_id' => $row->id]);
                    }
                }
            }, 'id');

        return $counts;
    }
}
