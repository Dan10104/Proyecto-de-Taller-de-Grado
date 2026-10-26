<?php

namespace App\Services\Traceability;

use Illuminate\Support\Facades\DB;

final class TraceabilityLock
{
    public function run(string $key, callable $operation): mixed
    {
        if (DB::transactionLevel() > 0 && ! app()->runningUnitTests()) {
            throw new TraceabilityUnavailable('BUSINESS_TRANSACTION_ACTIVE');
        }
        // Separate connection, connection-scoped MySQL advisory lock; no business row lock.
        $name = 'trace_lock_'.bin2hex(random_bytes(6));
        $lock = 'espacio-u:'.substr(hash('sha256', $key), 0, 48);
        config(['database.connections.'.$name => config('database.connections.'.config('database.default'))]);
        $connection = DB::connection($name);
        try {
            if ((int) $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
                throw new TraceabilityUnavailable('BUSY');
            }

            return $operation();
        } finally {
            try {
                $connection->selectOne('SELECT RELEASE_LOCK(?)', [$lock]);
            } finally {
                DB::purge($name);
                config(['database.connections.'.$name => null]);
            }
        }
    }
}
