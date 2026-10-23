<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Dotenv\Dotenv;

try {
    $root = dirname(__DIR__);
    $env = Dotenv::createArrayBacked($root)->load();
    $action = $argv[1] ?? 'status';
    if (! in_array($action, ['status', 'stop'], true)) {
        throw new RuntimeException('Unsupported action');
    }
    if (($env['DB_HOST'] ?? '') !== '127.0.0.1' || ($env['DB_PORT'] ?? '') !== '3308' || ($env['DB_DATABASE'] ?? '') !== 'espacio_u') {
        throw new RuntimeException('Unexpected local target');
    }
    $admin = $action === 'stop';
    $connection = new PDO(
        'mysql:host=127.0.0.1;port=3308;dbname=espacio_u;charset=utf8mb4',
        $admin ? 'root' : $env['DB_USERNAME'],
        $admin ? $env['MYSQL_DEV_ROOT_PASSWORD'] : $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );
    $row = $connection->query('SELECT VERSION() AS version, @@datadir AS data_dir, @@port AS port')->fetch(PDO::FETCH_ASSOC);
    $expected = realpath($root.'/.local/mysql/data');
    $actual = realpath($row['data_dir']);
    if ($expected === false || $actual === false || strcasecmp($expected, $actual) !== 0 || (int) $row['port'] !== 3308) {
        throw new RuntimeException('Instance ownership not verified');
    }
    if ($admin) {
        $connection->exec('SHUTDOWN');
        echo "Instancia MySQL del proyecto detenida correctamente.\n";
    } else {
        echo 'MySQL '.$row['version']." conectado a espacio_u en 127.0.0.1:3308; directorio del proyecto verificado.\n";
    }
} catch (Throwable $error) {
    // No revelar usuario, contraseña, DSN ampliado ni excepciones del proveedor.
    fwrite(STDERR, "No se pudo validar la instancia MySQL local del proyecto. Revisar .env y el estado local.\n");
    exit(1);
}
