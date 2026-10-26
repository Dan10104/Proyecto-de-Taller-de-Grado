<?php

return [
    'dashboard_max_buckets' => (int) env('DASHBOARD_MAX_BUCKETS', 1200),
    'max_rows' => (int) env('REPORT_EXPORT_MAX_ROWS', 10000),
    'timeout_seconds' => (int) env('REPORT_EXPORT_TIMEOUT_SECONDS', 30),
    'max_bytes' => (int) env('REPORT_EXPORT_MAX_BYTES', 67108864),
    'memory_bytes' => (int) env('REPORT_EXPORT_MEMORY_BYTES', 67108864),
    'temp_ttl_seconds' => (int) env('REPORT_TEMP_TTL_SECONDS', 3600),
    'chunk_size' => 250,
];
