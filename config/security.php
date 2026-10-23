<?php

return [
    'login_attempts' => 5,
    'login_decay_seconds' => 60,
    'login_ip_attempts' => 30,
    'bootstrap_email' => env('SEED_ADMIN_EMAIL'),
    'bootstrap_password' => env('SEED_ADMIN_PASSWORD'),
];
