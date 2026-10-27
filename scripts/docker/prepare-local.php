<?php

// No framework bootstrap and no access to the host development credentials.
$root = dirname(__DIR__, 2);
$path = $root.'/.env.docker';
$handle = @fopen($path, 'x');
if ($handle === false) {
    fwrite(STDERR, 'Local Docker configuration already exists or cannot be created.
');
    exit(1);
}
chmod($path, 0600);
$values = [
    'NOTIFICATIONS_EVENTS_FROM' => (new DateTimeImmutable('now', new DateTimeZone('America/La_Paz')))->format('Y-m-d H:i:s'),
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'DB_PASSWORD' => bin2hex(random_bytes(32)),
    'DB_MIGRATION_PASSWORD' => bin2hex(random_bytes(32)),
    'MYSQL_ROOT_PASSWORD' => bin2hex(random_bytes(32)),
    'IOT_MASTER_SECRET' => bin2hex(random_bytes(32)),
    'SEED_ADMIN_EMAIL' => 'docker-admin@espacio-u.test',
    'SEED_ADMIN_PASSWORD' => bin2hex(random_bytes(32)),
];
foreach ($values as $name => $value) {
    fwrite($handle, $name.'='.$value.PHP_EOL);
}
fclose($handle);
fwrite(STDOUT, 'Private .env.docker created. Credentials were not printed.
');
