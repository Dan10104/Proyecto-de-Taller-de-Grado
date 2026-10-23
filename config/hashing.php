<?php

$config = require base_path('vendor/laravel/framework/config/hashing.php');
$config['driver'] = 'argon2id';

return $config;
