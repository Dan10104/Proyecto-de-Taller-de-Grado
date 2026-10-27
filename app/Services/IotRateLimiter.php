<?php

namespace App\Services;

use App\Exceptions\IotException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class IotRateLimiter
{
    public function consume(string $scope, int $limit): void
    {
        try {
            $storeName = config('iot.rate_store');
            $driver = config('cache.stores.'.$storeName.'.driver');
            // Array/null/failover stores cannot provide the required cross-worker quota.
            if (! in_array($driver, ['file', 'redis', 'database'], true) || $limit < 1) {
                throw new IotException(503, 'temporarily_unavailable');
            }
            $key = 'iot:quota:'.hash('sha256', $scope);
            if ($driver === 'database') {
                $this->consumeDatabase($storeName, $key, $limit);

                return;
            }
            $store = Cache::store($storeName);
            // Existing file/Redis behavior: no lease expiry while a worker is suspended.
            $store->lock($key.':lock', 0)->block(1, function () use ($store, $key, $limit) {
                $window = $this->increment($store->get($key), $limit);
                if (! $store->put($key, $window, max(1, $window['until'] - now()->timestamp))) {
                    throw new IotException(503, 'temporarily_unavailable');
                }
            });
        } catch (IotException $error) {
            throw $error;
        } catch (Throwable) {
            throw new IotException(503, 'temporarily_unavailable');
        }
    }

    private function consumeDatabase(string $storeName, string $key, int $limit): void
    {
        $settings = config('cache.stores.'.$storeName);
        $connection = DB::connection($settings['connection']);
        $prefixed = Cache::store($storeName)->getStore()->getPrefix().$key;
        // A transaction-scoped row lock has no expiring lease. A crashed connection
        // rolls back; a suspended worker cannot let a second replica lose increments.
        $connection->transaction(function () use ($connection, $settings, $prefixed, $limit) {
            $query = $connection->table($settings['table'])->where('key', $prefixed);
            $connection->table($settings['table'])->insertOrIgnore([
                'key' => $prefixed, 'value' => serialize(['until' => 0, 'count' => 0]), 'expiration' => 0,
            ]);
            $row = $query->lockForUpdate()->first();
            $window = $this->increment(unserialize($row->value, ['allowed_classes' => false]), $limit);
            $query->update(['value' => serialize($window), 'expiration' => $window['until']]);
        }, 3);
    }

    private function increment(mixed $window, int $limit): array
    {
        $now = now()->timestamp;
        if (! is_array($window) || $now >= $window['until']) {
            $window = ['until' => $now + 60, 'count' => 0];
        }
        if ($window['count'] >= $limit) {
            throw new IotException(429, 'rate_limited', max(1, $window['until'] - $now));
        }
        $window['count']++;

        return $window;
    }
}
