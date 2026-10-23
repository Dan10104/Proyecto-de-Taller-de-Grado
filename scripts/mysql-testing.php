<?php

require dirname(__DIR__).'/vendor/autoload.php';
use Dotenv\Dotenv;
use Symfony\Component\Process\Process;

try {
    $root = dirname(__DIR__);
    if (file_exists($root.'/.env.testing')) {
        throw new RuntimeException('Testing ya configurado; no sobrescribir.');
    }
    $env = Dotenv::createArrayBacked($root)->load();
    if ($env['DB_HOST'] !== '127.0.0.1' || $env['DB_PORT'] !== '3308' || $env['DB_DATABASE'] !== 'espacio_u') {
        throw new RuntimeException;
    }
    $pdo = new PDO('mysql:host=127.0.0.1;port=3308;charset=utf8mb4', 'root', $env['MYSQL_DEV_ROOT_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $server = $pdo->query('SELECT @@datadir AS dir, @@port AS port')->fetch(PDO::FETCH_ASSOC);
    if (strcasecmp((string) realpath($server['dir']), (string) realpath($root.'/.local/mysql/data')) !== 0 || (int) $server['port'] !== 3308) {
        throw new RuntimeException;
    }
    if ($pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='espacio_u_testing'")->fetch()) {
        throw new RuntimeException('Conservar base existente.');
    }
    $name = 'test_'.bin2hex(random_bytes(8));
    $password = bin2hex(random_bytes(32));
    $key = base64_encode(random_bytes(32));
    $text = "APP_NAME=ESPACIO-U\nAPP_ENV=testing\nAPP_DEBUG=false\nAPP_KEY=base64:$key\nAPP_URL=http://127.0.0.1:8000\nAPP_CONFIG_CACHE=.local/testing/config.php\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3308\nDB_DATABASE=espacio_u_testing\nDB_USERNAME=$name\nDB_PASSWORD=$password\nDB_TIMEZONE=-04:00\nCACHE_STORE=array\nSESSION_DRIVER=array\nMAIL_MAILER=array\nQUEUE_CONNECTION=sync\n";
    // Restrict the file before writing any generated secret; never overwrite an existing file.
    $file = fopen($root.'/.env.testing', 'x');
    if ($file === false) {
        throw new RuntimeException;
    }
    fclose($file);
    if (PHP_OS_FAMILY === 'Windows') {
        $identity = new Process(['whoami.exe', '/user', '/fo', 'csv', '/nh']);
        $identity->mustRun();
        if (! preg_match('/S-1-5-[0-9-]+/', $identity->getOutput(), $match)) {
            throw new RuntimeException;
        }
        (new Process(['icacls.exe', $root.'/.env.testing', '/inheritance:r', '/grant:r', '*'.$match[0].':(F)', '*S-1-5-18:(F)']))->mustRun();
    } elseif (! chmod($root.'/.env.testing', 0600)) {
        throw new RuntimeException;
    }
    // Write secrets before provisioning so an interruption never loses generated credentials.
    if (file_put_contents($root.'/.env.testing', $text) === false) {
        throw new RuntimeException;
    }
    $pdo->exec('CREATE DATABASE espacio_u_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec("CREATE USER '$name'@'127.0.0.1' IDENTIFIED BY ".$pdo->quote($password));
    $pdo->exec("GRANT ALL PRIVILEGES ON espacio_u_testing.* TO '$name'@'127.0.0.1'");
    $test = new PDO('mysql:host=127.0.0.1;port=3308;dbname=espacio_u_testing', $name, $password);
    if ($test->query('SELECT DATABASE()')->fetchColumn() !== 'espacio_u_testing') {
        throw new RuntimeException;
    }
    try {
        $test->query('SELECT * FROM espacio_u.migrations');
        throw new LogicException;
    } catch (PDOException $denied) {
        if (($denied->errorInfo[1] ?? null) !== 1142) {
            throw new RuntimeException;
        }
    }
    echo "MySQL testing creado; conexión validada y acceso a desarrollo denegado. Credenciales solo en .env.testing.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se completó la preparación de testing. Conservar archivos locales y revisar estado, sin exponer credenciales.\n");
    exit(1);
}
