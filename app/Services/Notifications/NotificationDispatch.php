<?php

namespace App\Services\Notifications;

use App\Jobs\GenerateNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationDispatch
{
    public static function afterCommit(int $id): void
    {
        DB::afterCommit(static function () use ($id) {
            try {
                GenerateNotifications::dispatch($id)->onConnection('deferred');
            } catch (\Throwable) {
                Log::warning('notifications.dispatch_pending', ['activity_id' => $id]);
            }
        });
    }
}
