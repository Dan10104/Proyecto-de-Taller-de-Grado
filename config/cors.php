<?php

return [
    // IoT devices use server-to-server HMAC. No browser cross-origin client is authorized.
    // A future API must explicitly review its origins, methods and authentication first.
    'paths' => ['api/*'],
    'allowed_methods' => ['POST'],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-IoT-Sensor', 'X-IoT-Timestamp', 'X-IoT-Signature'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
