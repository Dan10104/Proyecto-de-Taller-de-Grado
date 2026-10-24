<?php

namespace App\Services;

use App\Exceptions\IotException;
use Illuminate\Support\Facades\Cache;
use Throwable;

class IotRateLimiter
{
    public function consume(string $scope, int $limit): void
    {
        try {
            // Array/null/failover stores cannot provide the required cross-worker quota.
            $storeName = config('iot.rate_store');
            if (! in_array(config('cache.stores.'.$storeName.'.driver'), ['file', 'redis'], true) || $limit < 1) {
                throw new IotException(503, 'temporarily_unavailable');
            }
            $store = Cache::store($storeName);
            $key = 'iot:quota:'.hash('sha256', $scope);
            // No lease expiry while a worker is suspended: a crashed owner fails
            // closed until an operator recovers the store with ingestion stopped.
            $store->lock($key.':lock', 0)->block(1, function () use ($store, $key, $limit) {
                $now = now()->timestamp;
                $window = $store->get($key);
                if (! is_array($window) || $now >= $window['until']) {
                    $window = ['until' => $now + 60, 'count' => 0];
                }
                if ($window['count'] >= $limit) {
                    throw new IotException(429, 'rate_limited', max(1, $window['until'] - $now));
                }
                $window['count']++;
                if (! $store->put($key, $window, max(1, $window['until'] - $now))) {
                    throw new IotException(503, 'temporarily_unavailable');
                }
            });
        } catch (IotException $error) {
            throw $error;
        } catch (Throwable) {
            throw new IotException(503, 'temporarily_unavailable');
        }
    }
}
