<?php

namespace App\Jobs;

use App\Services\Notifications\NotificationGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $activityId) {}

    public function handle(NotificationGenerationService $service): void
    {
        try {
            $service->generate($this->activityId);
        } catch (\Throwable) {
            // Durable activity is retried by the reconciler; never log SQL or payloads.
            Log::warning('notifications.generation_pending', ['activity_id' => $this->activityId]);
        }
    }
}
