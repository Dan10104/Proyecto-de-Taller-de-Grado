<?php

namespace Tests\Feature;

use App\Exceptions\IotException;
use App\Jobs\GenerateNotifications;
use App\Services\IotRateLimiter;
use App\Services\Notifications\NotificationDispatch;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\SecurityTestCase;

class DeploymentSharedStorageTest extends SecurityTestCase
{
    public function test_framework_tables_have_only_infrastructure_columns_and_no_academic_foreign_keys(): void
    {
        $expected = [
            'sessions' => ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity'],
            'cache' => ['key', 'value', 'expiration'], 'cache_locks' => ['key', 'owner', 'expiration'],
            'jobs' => ['id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at'],
        ];
        foreach ($expected as $table => $columns) {
            $this->assertSame($columns, Schema::getColumnListing($table));
            $this->assertSame([], Schema::getForeignKeys($table));
        }
        $this->assertTrue(Schema::hasTable('sesiones_acceso'));
        $this->assertFalse(Schema::hasTable('failed_jobs'));
    }

    public function test_session_payload_survives_new_handler_and_destroy_invalidates_shared_record(): void
    {
        $id = bin2hex(random_bytes(20));
        $one = new DatabaseSessionHandler(DB::connection(), 'sessions', 120);
        $two = new DatabaseSessionHandler(DB::connection(), 'sessions', 120);
        $payload = json_encode(['marker' => bin2hex(random_bytes(12))]);
        $this->assertTrue($one->write($id, $payload));
        $this->assertSame($payload, $two->read($id));
        $this->assertTrue($two->destroy($id));
        $this->assertSame('', $one->read($id));
        $this->assertDatabaseCount('sesiones_acceso', 0);
    }

    public function test_database_lock_excludes_another_owner_and_can_be_reacquired_after_release(): void
    {
        $store = Cache::store('database');
        $key = 'phase14:'.bin2hex(random_bytes(12));
        $first = $store->lock($key, 120);
        $second = $store->lock($key, 120);
        $this->assertTrue($first->get());
        $this->assertFalse($second->get());
        $this->assertFalse($second->release());
        $this->assertTrue($first->release());
        $this->assertTrue($second->get());
        $this->assertTrue($second->release());
    }

    public function test_database_iot_quota_is_shared_and_preserves_fixed_window_boundary(): void
    {
        config(['iot.rate_store' => 'database']);
        $scope = 'phase14:'.bin2hex(random_bytes(12));
        $this->freezeTime();
        (new IotRateLimiter)->consume($scope, 2);
        (new IotRateLimiter)->consume($scope, 2);
        try {
            (new IotRateLimiter)->consume($scope, 2);
            $this->fail('Third request must be rejected.');
        } catch (IotException $error) {
            $this->assertSame(429, $error->status);
        }
        $this->travel(60)->seconds();
        (new IotRateLimiter)->consume($scope, 2);
        $this->assertTrue(true);
        $this->travelBack();
    }

    public function test_database_iot_failure_is_closed_and_does_not_fallback_to_local_storage(): void
    {
        config(['iot.rate_store' => 'database', 'cache.stores.database.table' => 'missing_quota_table']);
        try {
            (new IotRateLimiter)->consume('phase14:missing', 2);
            $this->fail('Unavailable shared store must reject ingestion.');
        } catch (IotException $error) {
            $this->assertSame(503, $error->status);
        }
    }

    public function test_notification_dispatch_honors_configured_async_backend_and_rollback_does_not_dispatch(): void
    {
        Queue::fake();
        config(['queue.effects_connection' => 'database']);
        DB::transaction(fn () => NotificationDispatch::afterCommit(42));
        Queue::assertPushed(GenerateNotifications::class, fn ($job) => $job->connection === 'database' && $job->activityId === 42);
        Queue::fake();
        try {
            DB::transaction(function () {
                NotificationDispatch::afterCommit(43);
                throw new \RuntimeException('synthetic rollback');
            });
        } catch (\RuntimeException) {
        }
        Queue::assertNothingPushed();
    }

    public function test_database_queue_persists_id_only_job_and_reserves_it_once(): void
    {
        config(['queue.connections.database.after_commit' => false]);
        $queue = Queue::connection('database');
        $queue->push(new GenerateNotifications(123456));
        $this->assertSame(1, $queue->size());
        $job = $queue->pop();
        $this->assertNotNull($job);
        $this->assertNull($queue->pop());
        $this->assertStringContainsString('activityId', $job->getRawBody());
        $this->assertStringNotContainsString($this->password, $job->getRawBody());
        $job->delete();
        $this->assertSame(0, $queue->size());
    }
}
