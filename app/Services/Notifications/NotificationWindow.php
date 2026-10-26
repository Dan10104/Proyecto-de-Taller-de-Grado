<?php

namespace App\Services\Notifications;

use Carbon\CarbonImmutable;

class NotificationWindow
{
    public function start(): ?CarbonImmutable
    {
        $value = config('notifications.events_from');
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $value)) {
            return null;
        }
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $value, 'America/La_Paz');

            return $date->format('Y-m-d H:i:s') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function includes(CarbonImmutable $event, CarbonImmutable $time): bool
    {
        $start = $this->start();

        return $start !== null && $event->gte($start) && $event->lte($time) && $event->gte($time->subHours(24));
    }
}
