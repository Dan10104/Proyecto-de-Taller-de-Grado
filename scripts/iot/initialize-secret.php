<?php

// Generate directly into an ignored local environment file; never print the value.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$name = $argv[1] ?? '.env';
if (! in_array($name, ['.env', '.env.testing'], true)) {
    fwrite(STDERR, 'Destino permitido: .env o .env.testing.'.PHP_EOL);
    exit(1);
}
$path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$name;
try {
    $text = file_get_contents($path);
    if ($text === false) {
        throw new RuntimeException;
    }
    $values = Dotenv\Dotenv::parse($text);
    if (! in_array($values['APP_ENV'] ?? '', ['local', 'testing'], true)) {
        throw new RuntimeException;
    }
    if (($values['IOT_MASTER_SECRET'] ?? '') !== '') {
        fwrite(STDOUT, 'Ya existe una configuración privada. No se ha modificado ni rotado.'.PHP_EOL);
        exit(0);
    }
    $text = preg_replace('/^IOT_MASTER_SECRET=.*\R?/m', '', $text);
    $text = rtrim($text).PHP_EOL.'IOT_MASTER_SECRET='.bin2hex(random_bytes(32)).PHP_EOL;
    if (file_put_contents($path, $text, LOCK_EX) === false) {
        throw new RuntimeException;
    }
    fwrite(STDOUT, 'Secreto local generado y guardado sin mostrarlo. Recargue la configuración del entorno.'.PHP_EOL);
} catch (Throwable) {
    fwrite(STDERR, 'No fue posible inicializar el entorno privado. Compruebe el archivo local y sus permisos.'.PHP_EOL);
    exit(1);
}
