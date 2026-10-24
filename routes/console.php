<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reservas:evaluar-liberacion')
    ->cron(config('release.schedule'))
    ->withoutOverlapping(10);
