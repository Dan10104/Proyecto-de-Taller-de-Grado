<?php

return [
    'schedule' => env('RELEASE_SCHEDULE', '* * * * *'),
    'batch_size' => (int) env('RELEASE_BATCH_SIZE', 100),
];
