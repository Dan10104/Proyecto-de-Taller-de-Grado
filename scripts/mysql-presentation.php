<?php

/*
 * Aprovisiona una base de datos adicional y vacía en la instancia MySQL local del proyecto
 * (por ejemplo, la base de presentación), sin tocar las bases existentes.
 *
 *   php scripts/mysql-presentation.php <nombre_de_base>
 *
 * - Crea el esquema y un usuario propio con privilegios únicamente sobre ese esquema.
 * - Escribe las credenciales en un archivo local nuevo `.env.<nombre_de_base>` (ignorado por Git),
 *   copia de `.env` con DB_DATABASE, DB_USERNAME y DB_PASSWORD sustituidos.
 * - Nunca sobrescribe archivos ni bases existentes y nunca imprime credenciales.
 *
 * La base no recibe datos: después se ejecutan las migraciones, el catálogo de roles y permisos
 * y el alta del administrador con la configuración activa (ver docs/PRESENTATION_DATABASE.md).
 */

require dirname(__DIR__).'/vendor/autoload.php';

use Dotenv\Dotenv;
use Symfony\Component\Process\Process;

try {
    $root = dirname(__DIR__);
    $database = (string) ($argv[1] ?? '');
    $reserved = ['mysql', 'sys', 'information_schema', 'performance_schema'];
    if (! preg_match('/\A[a-z][a-z0-9_]{2,48}\z/', $database) || in_array($database, $reserved, true)) {
        throw new DomainException('Indique un nombre de base válido: minúsculas, dígitos y guion bajo (3 a 49 caracteres).');
    }
    $target = $root.'/.env.'.$database;
    if (file_exists($target)) {
        throw new DomainException('Ya existe un archivo de entorno para esa base; no se sobrescribe.');
    }
    $source = file_get_contents($root.'/.env');
    $env = Dotenv::createArrayBacked($root)->load();
    if ($source === false || ($env['DB_HOST'] ?? '') !== '127.0.0.1' || ($env['DB_PORT'] ?? '') !== '3308') {
        throw new DomainException('La configuración local activa no apunta a la instancia MySQL del proyecto.');
    }
    $pdo = new PDO('mysql:host=127.0.0.1;port=3308;charset=utf8mb4', 'root', $env['MYSQL_DEV_ROOT_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $server = $pdo->query('SELECT @@datadir AS dir, @@port AS port')->fetch(PDO::FETCH_ASSOC);
    if (strcasecmp((string) realpath($server['dir']), (string) realpath($root.'/.local/mysql/data')) !== 0 || (int) $server['port'] !== 3308) {
        throw new DomainException('La instancia MySQL no es la del proyecto.');
    }
    $exists = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $exists->execute([$database]);
    if ($exists->fetch()) {
        throw new DomainException('La base indicada ya existe; se conserva sin cambios.');
    }
    $others = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME NOT IN ('mysql','sys','information_schema','performance_schema')")->fetchAll(PDO::FETCH_COLUMN);

    $user = 'app_'.bin2hex(random_bytes(8));
    $password = bin2hex(random_bytes(32));
    $replace = ['DB_DATABASE' => $database, 'DB_USERNAME' => $user, 'DB_PASSWORD' => $password];
    $text = $source;
    foreach ($replace as $key => $value) {
        $text = preg_replace('/^'.$key.'=.*$/m', $key.'='.$value, $text, 1, $count);
        if ($count !== 1) {
            throw new DomainException('La configuración local no contiene las claves de base de datos esperadas.');
        }
    }

    // Restringir el archivo antes de escribir secretos; nunca sobrescribir uno existente.
    $file = fopen($target, 'x');
    if ($file === false) {
        throw new DomainException('No se pudo crear el archivo de entorno.');
    }
    fclose($file);
    if (PHP_OS_FAMILY === 'Windows') {
        // Utilidades de Windows por ruta completa: otros shells exponen un whoami distinto.
        $system = rtrim((string) getenv('SystemRoot'), '\\/').DIRECTORY_SEPARATOR.'System32'.DIRECTORY_SEPARATOR;
        $identity = new Process([$system.'whoami.exe', '/user', '/fo', 'csv', '/nh']);
        $identity->mustRun();
        if (! preg_match('/S-1-5-[0-9-]+/', $identity->getOutput(), $match)) {
            throw new DomainException('No se pudo identificar al usuario actual.');
        }
        (new Process([$system.'icacls.exe', $target, '/inheritance:r', '/grant:r', '*'.$match[0].':(F)', '*S-1-5-18:(F)']))->mustRun();
    } elseif (! chmod($target, 0600)) {
        throw new DomainException('No se pudo restringir el archivo de entorno.');
    }
    // Guardar las credenciales antes de aprovisionar: una interrupción nunca las pierde.
    if (file_put_contents($target, $text) === false) {
        throw new DomainException('No se pudo escribir el archivo de entorno.');
    }

    $pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec("CREATE USER '$user'@'127.0.0.1' IDENTIFIED BY ".$pdo->quote($password));
    $pdo->exec('GRANT ALL PRIVILEGES ON `'.$database."`.* TO '$user'@'127.0.0.1'");

    $check = new PDO('mysql:host=127.0.0.1;port=3308;dbname='.$database, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($check->query('SELECT DATABASE()')->fetchColumn() !== $database) {
        throw new DomainException('No se pudo validar la conexión a la base nueva.');
    }
    // El usuario nuevo no debe poder leer ninguna otra base del proyecto.
    foreach ($others as $other) {
        try {
            $check->query('SELECT 1 FROM `'.$other.'`.migrations LIMIT 1');
            throw new DomainException('El usuario nuevo tiene acceso a otra base.');
        } catch (PDOException $denied) {
            if (! in_array($denied->errorInfo[1] ?? null, [1044, 1142], true)) {
                throw new DomainException('No se pudo comprobar el aislamiento de la base nueva.');
            }
        }
    }
    echo "Base {$database} creada vacía; conexión validada y acceso a las demás bases denegado. Credenciales solo en .env.{$database}.\n";
} catch (Throwable $error) {
    $safe = $error instanceof DomainException ? $error->getMessage() : 'Revise el estado local.';
    fwrite(STDERR, "No se completó la preparación de la base. {$safe} No se muestran credenciales.\n");
    exit(1);
}
