<?php

Schedule::command('trazabilidad:reconciliar')->everyMinute()->withoutOverlapping(10);

use Illuminate\Support\Facades\Schedule;

Schedule::command('reservas:evaluar-liberacion')
    ->cron(config('release.schedule'))
    ->withoutOverlapping(10);

Schedule::command('notificaciones:reconciliar')->cron(config('notifications.reconcile_schedule'))->withoutOverlapping(10);
Schedule::command('prestamos:detectar-vencimientos')->cron(config('notifications.overdue_schedule'))->withoutOverlapping(10);
