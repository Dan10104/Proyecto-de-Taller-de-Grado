<?php

return [
    'master_secret' => env('IOT_MASTER_SECRET'),
    'request_max_skew_seconds' => (int) env('IOT_REQUEST_MAX_SKEW_SECONDS', 300),
    'monitoring_freshness_seconds' => (int) env('IOT_MONITORING_FRESHNESS_SECONDS', 300),
    'rate_limit_per_minute' => (int) env('IOT_RATE_LIMIT_PER_MINUTE', 120),
    'max_body_bytes' => (int) env('IOT_MAX_BODY_BYTES', 4096),
    'origin_rate_limit_per_minute' => (int) env('IOT_ORIGIN_RATE_LIMIT_PER_MINUTE', 1200),
    // One host, coordinated across PHP workers; a shared store is required on multiple hosts.
    'rate_store' => 'file',
];
