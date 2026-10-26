<?php

namespace Tests\Feature;

use App\Enums\ActivityAction as Action;
use App\Models\RegistroActividad;
use App\Services\Activity\ActivityAttempt;
use App\Services\Activity\ActivityRecorder;
use App\Services\Activity\ActivityTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\IotTestCase;

class ActivityTransactionTest extends IotTestCase
{
    public function test_recorder_requires_real_transaction(): void
    {
        $admin = $this->user('Administrador');
        $this->expectException(\LogicException::class);
        app(ActivityRecorder::class)->success(Action::USUARIO_EDITADO, $admin);
    }

    public function test_mysql_retry_discards_first_success_and_commits_only_winner(): void
    {
        $admin = $this->user('Administrador');
        $audit = new ActivityAttempt($admin);
        $attempts = 0;
        app(ActivityTransaction::class)->run(function () use ($audit, $admin, &$attempts) {
            $audit->admit(Action::USUARIO_EDITADO);
            app(ActivityRecorder::class)->success(Action::USUARIO_EDITADO, $admin);
            $attempts++;
            if ($attempts === 1) {
                DB::statement("SIGNAL SQLSTATE '40001' SET MYSQL_ERRNO = 1213, MESSAGE_TEXT = 'Deadlock found when trying to get lock'");
            }
        }, $audit);
        $this->assertSame(2, $attempts);
        $this->assertSame('EXITO', RegistroActividad::sole()->resultado->value);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_exhausted_mysql_retries_produce_one_error_without_success(): void
    {
        $admin = $this->user('Administrador');
        $audit = new ActivityAttempt($admin);
        $attempts = 0;
        try {
            app(ActivityTransaction::class)->run(function () use ($audit, &$attempts) {
                $audit->admit(Action::USUARIO_EDITADO);
                $attempts++;
                DB::statement("SIGNAL SQLSTATE '40001' SET MYSQL_ERRNO = 1213, MESSAGE_TEXT = 'Deadlock found when trying to get lock'");
            }, $audit);
        } catch (QueryException) {
        }
        $this->assertSame(3, $attempts);
        $this->assertSame('ERROR', RegistroActividad::sole()->resultado->value);
    }

    public function test_postcommit_consumer_failure_never_turns_confirmed_success_into_error(): void
    {
        $admin = $this->user('Administrador');
        $audit = new ActivityAttempt($admin);
        try {
            app(ActivityTransaction::class)->run(function () use ($audit, $admin) {
                $audit->admit(Action::USUARIO_EDITADO);
                app(ActivityRecorder::class)->success(Action::USUARIO_EDITADO, $admin);
                DB::afterCommit(fn () => throw new \RuntimeException('Consumer unavailable'));
            }, $audit);
        } catch (\RuntimeException) {
        }
        $this->assertSame('EXITO', RegistroActividad::sole()->resultado->value);
        $this->assertSame(0, DB::transactionLevel());
    }
}
