<?php

/**
 * Crea la base desechable de la suite automatizada, `espacio_u_testing_efimera`, en la instancia MySQL local del
 * proyecto (127.0.0.1:3308) y concede sobre ella, y solo sobre ella, privilegios a la cuenta de pruebas ya
 * existente en `.env.testing`.
 *
 * - La suite (`php artisan test`) y la integración Blockchain local vacían su base en cada ejecución. Por eso usan
 *   esta base desechable y nunca `espacio_u_testing`, que conserva datos entre ejecuciones.
 * - Es idempotente: no borra ni modifica ninguna base existente, no crea cuentas y no cambia `.env.testing`.
 * - No imprime credenciales.
 *
 * Uso (con MySQL en marcha):  php scripts/mysql-testing-suite.php
 */

require dirname(__DIR__).'/vendor/autoload.php';
use Dotenv\Dotenv;

const SUITE_DATABASE = 'espacio_u_testing_efimera';

try {
    $root = dirname(__DIR__);
    if (! is_file($root.'/.env.testing')) {
        throw new DomainException('Falta .env.testing: prepare primero el entorno de pruebas con scripts/mysql-testing.php.');
    }
    $local = Dotenv::createArrayBacked($root)->load();
    $testing = Dotenv::createArrayBacked($root, '.env.testing')->load();
    foreach ([$local, $testing] as $env) {
        if (($env['DB_HOST'] ?? '') !== '127.0.0.1' || ($env['DB_PORT'] ?? '') !== '3308') {
            throw new DomainException('La configuración local no apunta a la instancia MySQL del proyecto.');
        }
    }
    $user = (string) ($testing['DB_USERNAME'] ?? '');
    if (($testing['APP_ENV'] ?? '') !== 'testing' || ! preg_match('/\Atest_[a-f0-9]{16}\z/', $user)) {
        throw new DomainException('.env.testing no corresponde a la cuenta de pruebas del proyecto.');
    }
    $pdo = new PDO('mysql:host=127.0.0.1;port=3308;charset=utf8mb4', 'root', $local['MYSQL_DEV_ROOT_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $server = $pdo->query('SELECT @@datadir AS dir, @@port AS port')->fetch(PDO::FETCH_ASSOC);
    if (strcasecmp((string) realpath($server['dir']), (string) realpath($root.'/.local/mysql/data')) !== 0 || (int) $server['port'] !== 3308) {
        throw new DomainException('La instancia MySQL no es la del proyecto.');
    }
    $account = $pdo->prepare("SELECT COUNT(*) FROM mysql.user WHERE user = ? AND host = '127.0.0.1'");
    $account->execute([$user]);
    if ((int) $account->fetchColumn() !== 1) {
        throw new DomainException('La cuenta de pruebas no existe en la instancia MySQL del proyecto.');
    }
    $others = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME NOT IN ('mysql','sys','information_schema','performance_schema','espacio_u_testing','".SUITE_DATABASE."')")->fetchAll(PDO::FETCH_COLUMN);

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.SUITE_DATABASE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    // Nombre exacto: el guion bajo es comodín en los privilegios de MySQL y aquí se escapa.
    $pdo->exec('GRANT ALL PRIVILEGES ON `'.str_replace('_', '\\_', SUITE_DATABASE)."`.* TO '$user'@'127.0.0.1'");

    $check = new PDO('mysql:host=127.0.0.1;port=3308;dbname='.SUITE_DATABASE, $user, (string) ($testing['DB_PASSWORD'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($check->query('SELECT DATABASE()')->fetchColumn() !== SUITE_DATABASE) {
        throw new DomainException('No se pudo validar la conexión a la base de la suite.');
    }
    // La cuenta de pruebas sigue sin poder leer las bases de desarrollo ni de presentación.
    foreach ($others as $other) {
        try {
            $check->query('SELECT 1 FROM `'.$other.'`.migrations LIMIT 1');
            throw new DomainException('La cuenta de pruebas tiene acceso a otra base.');
        } catch (PDOException $denied) {
            if (! in_array($denied->errorInfo[1] ?? null, [1044, 1142], true)) {
                throw new DomainException('No se pudo comprobar el aislamiento de la cuenta de pruebas.');
            }
        }
    }
    echo 'Base '.SUITE_DATABASE." lista para la suite; conexión validada y acceso a las demás bases denegado. No se mostró ninguna credencial.\n";
} catch (DomainException $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
} catch (Throwable $error) {
    fwrite(STDERR, "No se completó la preparación de la base de la suite. Revisar el estado sin exponer credenciales.\n");
    exit(1);
}
