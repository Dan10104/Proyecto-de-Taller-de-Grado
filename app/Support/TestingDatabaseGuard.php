<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TestingDatabaseGuard
{
    /**
     * The only schema that automated tests and destructive maintenance commands may use. It is disposable:
     * every run empties it. The persistent `espacio_u_testing` schema is deliberately not accepted here.
     */
    public const DATABASE = 'espacio_u_testing_efimera';

    public static function check(?string $connection = null): void
    {
        $db = DB::connection($connection);
        if (! app()->environment('testing') || $db->getDriverName() !== 'mysql'
            || $db->getConfig('database') !== self::DATABASE
            || $db->getConfig('host') !== '127.0.0.1' || (int) $db->getConfig('port') !== 3308
            || $db->getConfig('unix_socket') || $db->getConfig('url')) {
            throw new RuntimeException('Operación rechazada: se exige MySQL local '.self::DATABASE.' y entorno testing.');
        }
        $target = $db->selectOne('SELECT DATABASE() AS db_name, @@port AS port, @@datadir AS data_dir');
        $expected = realpath(base_path('.local/mysql/data'));
        if ($target->db_name !== self::DATABASE || (int) $target->port !== 3308
            || ! $expected || strcasecmp($expected, (string) realpath($target->data_dir)) !== 0) {
            throw new RuntimeException('Destino efectivo de testing no autorizado.');
        }
    }
}
