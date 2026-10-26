<?php

namespace App\Services\Activity;

use App\Exceptions\EquipmentConflictException;
use App\Exceptions\IotException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DetectsLostConnections;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ActivityTransaction
{
    use DetectsLostConnections;

    public function run(callable $operation, ?ActivityAttempt $audit = null): mixed
    {
        if (! $audit) {
            return DB::transaction($operation, 3);
        }
        $level = DB::transactionLevel();
        $callbackFailure = null;
        try {
            return DB::transaction(function () use ($operation, $audit, &$callbackFailure) {
                $audit->reset();
                $callbackFailure = null;
                try {
                    return $operation();
                } catch (Throwable $e) {
                    $callbackFailure = $e;
                    throw $e;
                }
            }, 3);
        } catch (Throwable $e) {
            // Same callback exception + restored nesting proves Laravel completed rollback.
            // A commit/afterCommit failure never came from this callback; lost connections are uncertain.
            $rolledBack = $callbackFailure === $e && DB::transactionLevel() === $level && ! $this->causedByLostConnection($e);
            $rejected = $audit->rejection === $e;
            $excluded = $e instanceof EquipmentConflictException || $e instanceof IotException || $e instanceof ValidationException || $e instanceof AuthorizationException || $e instanceof ModelNotFoundException || $e instanceof HttpExceptionInterface
                || ($e instanceof QueryException && ($e->errorInfo[1] ?? null) === 1062 && ! $audit->duplicateExhausted);
            if ($rolledBack && $audit->action && ($rejected || ! $excluded)) {
                try {
                    DB::transaction(fn () => app(ActivityRecorder::class)->record([
                        ...$audit->context, 'actor_id' => (int) $audit->actor->id, 'accion' => $audit->action->value,
                        'resultado' => $rejected ? 'RECHAZO' : 'ERROR', 'descripcion' => ActivityDescription::failure($rejected),
                    ]));
                } catch (Throwable) {
                    Log::warning('activity.attempt_unavailable', ['action' => $audit->action->value]);
                }
            }
            throw $e;
        }
    }
}
