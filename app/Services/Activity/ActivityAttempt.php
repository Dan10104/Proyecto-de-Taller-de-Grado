<?php

namespace App\Services\Activity;

use App\Enums\ActivityAction;
use App\Models\Usuario;
use Throwable;

final class ActivityAttempt
{
    public bool $duplicateExhausted = false;

    public ?ActivityAction $action = null;

    public array $context = [];

    public ?Throwable $rejection = null;

    public function __construct(public readonly Usuario $actor) {}

    public function reset(): void
    {
        $this->action = null;
        $this->context = [];
        $this->rejection = null;
    }

    // Called only after authorization, validation and resolving existing references under locks.
    public function admit(ActivityAction $action, array $existingContext = []): void
    {
        $this->action = $action;
        $this->context = $existingContext;
    }

    public function reject(Throwable $exception): never
    {
        if ($this->action?->rejectable()) {
            $this->rejection = $exception;
        }
        throw $exception;
    }
}
