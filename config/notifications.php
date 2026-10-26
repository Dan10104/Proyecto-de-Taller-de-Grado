<?php

return [
    // Set once when RF21 is enabled. Missing/invalid configuration fails closed.
    'events_from' => env('NOTIFICATIONS_EVENTS_FROM'),
    'reconcile_schedule' => env('NOTIFICATIONS_RECONCILE_SCHEDULE', '* * * * *'),
    'overdue_schedule' => env('NOTIFICATIONS_OVERDUE_SCHEDULE', '* * * * *'),
];
