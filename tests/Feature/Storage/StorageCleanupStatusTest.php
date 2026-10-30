<?php

namespace Tests\Feature\Storage;

use App\Contracts\ScreenshotService;
use App\Helpers\StorageCleaner;
use App\Helpers\StorageCleanupStatus;
use App\Jobs\RotateScreenshots;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class StorageCleanupStatusTest extends TestCase
{
    private const URI = 'about/storage';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Half of the "total" space is used: the threshold alone decides whether thinning is needed
        config(['cleaner.total_space' => StorageCleaner::getFreeSpace() * 2]);
    }

    private function needThinning(bool $need): void
    {
        config(['cleaner.threshold' => $need ? 0 : 101]);
    }

    private function admin()
    {
        return UserFactory::refresh()->asAdmin()->withTokens()->create();
    }

    public function test_guest_cannot_read_or_request_cleanup(): void
    {
        Queue::fake();

        $this->getJson(self::URI)->assertUnauthorized();
        $this->postJson(self::URI)->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    public function test_non_admin_cannot_read_or_request_cleanup(): void
    {
        Queue::fake();
        $user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->actingAs($user)->getJson(self::URI)->assertForbidden();
        $this->actingAs($user)->postJson(self::URI)->assertForbidden();

        Queue::assertNothingPushed();
        $this->assertSame(StorageCleanupStatus::IDLE, StorageCleanupStatus::get()['status']);
    }

    public function test_admin_reads_compatible_storage_info(): void
    {
        $this->actingAs($this->admin())->getJson(self::URI)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'space' => ['left', 'used', 'total'],
                    'screenshots_available',
                    'thinning' => ['now', 'last', 'status', 'requested_at', 'finished_at'],
                ],
            ])
            ->assertJsonPath('data.thinning.now', false)
            ->assertJsonPath('data.thinning.last', null)
            ->assertJsonPath('data.thinning.status', StorageCleanupStatus::IDLE);
    }

    public function test_request_is_reported_as_queued_not_running(): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

        Queue::assertPushed(RotateScreenshots::class, 1);

        $response = $this->actingAs($admin)->getJson(self::URI)
            ->assertOk()
            ->assertJsonPath('data.thinning.status', StorageCleanupStatus::QUEUED)
            ->assertJsonPath('data.thinning.now', false)
            ->assertJsonPath('data.thinning.last', null);

        $this->assertNotNull($response->json('data.thinning.requested_at'));
    }

    public function test_duplicate_requests_queue_one_cleanup(): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();
        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

        StorageCleanupStatus::markRunning('scheduled');
        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

        Queue::assertPushed(RotateScreenshots::class, 1);
    }

    public function test_successful_cleanup_is_completed(): void
    {
        Queue::fake();
        $this->needThinning(true);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

        $this->artisan('cattr:screenshots:rotate')->assertSuccessful();

        $response = $this->actingAs($admin)->getJson(self::URI)
            ->assertOk()
            ->assertJsonPath('data.thinning.status', StorageCleanupStatus::COMPLETED)
            ->assertJsonPath('data.thinning.now', false);

        $this->assertNotNull($response->json('data.thinning.last'));
        $this->assertSame($response->json('data.thinning.last'), $response->json('data.thinning.finished_at'));
        // A run of the command itself (scheduler) is not the requested one
        $this->assertNull($response->json('data.thinning.requested_at'));

        // The run lock is released: another cleanup can be requested and run
        $this->assertNotNull(StorageCleanupStatus::queue());
    }

    public function test_cleanup_that_is_not_needed_is_skipped(): void
    {
        Queue::fake();
        $this->needThinning(false);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

        $this->artisan('cattr:screenshots:rotate')
            ->expectsOutputToContain('hasn\'t come')
            ->assertSuccessful();

        $response = $this->actingAs($admin)->getJson(self::URI)
            ->assertJsonPath('data.thinning.status', StorageCleanupStatus::SKIPPED)
            ->assertJsonPath('data.thinning.now', false)
            ->assertJsonPath('data.thinning.last', null);

        $this->assertNotNull($response->json('data.thinning.finished_at'));
    }

    public function test_requested_job_runs_the_cleanup(): void
    {
        // QUEUE_CONNECTION=sync: the job runs within the request
        $this->needThinning(true);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

        $this->assertSame(StorageCleanupStatus::COMPLETED, StorageCleanupStatus::get()['status']);
        $this->assertNotNull(StorageCleanupStatus::last());
    }

    public function test_exception_marks_cleanup_failed_and_releases_the_lock(): void
    {
        $this->needThinning(true);
        $this->app->bind(ScreenshotService::class, static fn () => throw new RuntimeException('disk error'));

        StorageCleanupStatus::queue();

        try {
            $this->artisan('cattr:screenshots:rotate')->run();
            $this->fail('The exception is not rethrown');
        } catch (RuntimeException $e) {
            $this->assertSame('disk error', $e->getMessage());
        }

        $status = StorageCleanupStatus::get();
        $this->assertSame(StorageCleanupStatus::FAILED, $status['status']);
        $this->assertNotNull($status['finished_at']);
        $this->assertNull(StorageCleanupStatus::last());

        $lock = StorageCleanupStatus::runLock('probe');
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_concurrent_run_does_not_start_while_the_lock_is_held(): void
    {
        $this->needThinning(true);
        StorageCleanupStatus::markRunning('other');

        $lock = StorageCleanupStatus::runLock('other');
        $this->assertTrue($lock->get());

        $this->artisan('cattr:screenshots:rotate')
            ->expectsOutputToContain('Another thinning is in progress')
            ->assertSuccessful();

        // The run holding the lock records the outcome, not the one that backed off
        $this->assertSame(StorageCleanupStatus::RUNNING, StorageCleanupStatus::get()['status']);

        $lock->release();
    }

    public function test_request_no_worker_picked_up_is_reported_as_failed(): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

        $this->travel(config('cleaner.queued_timeout') + 1)->seconds();

        $this->actingAs($admin)->getJson(self::URI)
            ->assertJsonPath('data.thinning.status', StorageCleanupStatus::FAILED);

        // The admin can request it again
        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();
        Queue::assertPushed(RotateScreenshots::class, 2);
    }

    public function test_run_without_heartbeat_is_reported_as_failed_and_can_be_requested_again(): void
    {
        Queue::fake();
        $admin = $this->admin();

        // A worker that died during the run: status running, lock never released
        StorageCleanupStatus::markRunning('dead');
        $this->assertTrue(StorageCleanupStatus::runLock('dead')->get());

        $this->travel(config('cleaner.running_timeout') + 1)->seconds();

        $this->actingAs($admin)->getJson(self::URI)
            ->assertJsonPath('data.thinning.status', StorageCleanupStatus::FAILED)
            ->assertJsonPath('data.thinning.now', false);

        $this->actingAs($admin)->postJson(self::URI)->assertNoContent();
        Queue::assertPushed(RotateScreenshots::class, 1);

        // The lock is not taken from the run: the new one waits for it (released by the failed job or its TTL)
        $this->assertFalse(StorageCleanupStatus::runLock('probe')->get());
    }

    /**
     * A scheduled run (no queue timeout) takes longer than running_timeout between heartbeats and is replaced
     * by a request while it is still alive.
     */
    public function test_live_replaced_run_neither_runs_next_to_nor_overwrites_the_new_request(): void
    {
        Queue::fake();
        config(['cleaner.lock_wait' => 1]);

        StorageCleanupStatus::markRunning('scheduled');
        $this->assertTrue(StorageCleanupStatus::runLock('scheduled')->get());

        $this->travel(config('cleaner.running_timeout') + 1)->seconds();
        $requested = StorageCleanupStatus::queue();
        $this->assertNotNull($requested);

        // The replaced run goes on: its heartbeat and outcome do not overwrite the request
        StorageCleanupStatus::touch('scheduled');
        StorageCleanupStatus::markCompleted('scheduled');
        $this->assertSame(StorageCleanupStatus::QUEUED, StorageCleanupStatus::get()['status']);
        $this->assertNotNull(StorageCleanupStatus::last());

        // The request does not run next to it (block() measures the wait with the real clock)
        $this->travelBack();

        try {
            (new RotateScreenshots($requested))->handle();
            $this->fail('The request runs next to a live run');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('still held', $e->getMessage());
        }
    }

    public function test_heartbeat_keeps_a_long_run_alive(): void
    {
        StorageCleanupStatus::markRunning('long');

        $this->travel(config('cleaner.running_timeout') - 1)->seconds();
        StorageCleanupStatus::touch('long');
        $this->travel(config('cleaner.running_timeout') - 1)->seconds();

        $this->assertTrue(StorageCleanupStatus::isRunning());
    }

    public function test_failed_job_clears_the_active_status(): void
    {
        $runId = StorageCleanupStatus::queue();
        StorageCleanupStatus::markRunning($runId);
        $this->assertTrue(StorageCleanupStatus::runLock($runId)->get());

        // Called by the worker when it kills the job on timeout
        (new RotateScreenshots($runId))->failed(new RuntimeException('timeout'));

        $this->assertSame(StorageCleanupStatus::FAILED, StorageCleanupStatus::get()['status']);

        $lock = StorageCleanupStatus::runLock('probe');
        $this->assertTrue($lock->get());
        $lock->release();
    }

    /**
     * A worker crashed during run A, run B was requested in its place; the reservation of A's job becomes
     * available after retry_after and the next worker fails it (tries = 1) while B is still running.
     */
    public function test_failed_job_of_a_lost_run_does_not_touch_the_new_run(): void
    {
        Queue::fake();

        $lost = StorageCleanupStatus::queue();
        StorageCleanupStatus::markRunning($lost);
        $this->assertTrue(StorageCleanupStatus::runLock($lost)->get());

        $this->travel(config('cleaner.running_timeout') + 1)->seconds();
        // The crashed worker's lock is gone (TTL), the new run takes it
        StorageCleanupStatus::runLock($lost)->release();

        $current = StorageCleanupStatus::queue();
        $this->assertNotNull($current);
        StorageCleanupStatus::markRunning($current);
        $this->assertTrue(StorageCleanupStatus::runLock($current)->get());

        (new RotateScreenshots($lost))->failed(new RuntimeException('max attempts exceeded'));

        $this->assertSame(StorageCleanupStatus::RUNNING, StorageCleanupStatus::get()['status']);
        $this->assertFalse(StorageCleanupStatus::runLock('probe')->get());
        $this->assertNull(StorageCleanupStatus::queue());
    }

    /**
     * Request A was not picked up in time and was replaced by request B; A's job is picked up before B's.
     */
    public function test_job_of_a_replaced_request_does_not_take_over_the_new_one(): void
    {
        Queue::fake();
        $this->needThinning(true);

        $replaced = StorageCleanupStatus::queue();
        $this->travel(config('cleaner.queued_timeout') + 1)->seconds();
        $current = StorageCleanupStatus::queue();
        $this->assertNotNull($current);

        $this->artisan('cattr:screenshots:rotate', ['--run' => $replaced])
            ->expectsOutputToContain('replaced by a newer one')
            ->assertSuccessful();

        $status = StorageCleanupStatus::get();
        $this->assertSame(StorageCleanupStatus::QUEUED, $status['status']);
        $this->assertNull(StorageCleanupStatus::last());

        // The lock of the replaced run is released: the new request runs
        (new RotateScreenshots($current))->handle();
        $this->assertSame(StorageCleanupStatus::COMPLETED, StorageCleanupStatus::get()['status']);
    }

    /**
     * The run lock stays held by another run (e.g. a replaced request checking it is not current, or a worker
     * gone without releasing it) while the request is still the current one: it must not be dropped silently.
     */
    public function test_current_request_is_not_dropped_while_the_lock_is_held(): void
    {
        Queue::fake();
        config(['cleaner.lock_wait' => 1]);

        $runId = StorageCleanupStatus::queue();
        $this->assertTrue(StorageCleanupStatus::runLock('other')->get());

        try {
            (new RotateScreenshots($runId))->handle();
            $this->fail('The request is dropped');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('still held', $e->getMessage());
        }

        // The worker fails the job: the request is reported as failed and can be requested again
        (new RotateScreenshots($runId))->failed();
        $this->assertSame(StorageCleanupStatus::FAILED, StorageCleanupStatus::get()['status']);
    }

    public function test_job_passes_its_run_to_the_command(): void
    {
        $this->needThinning(true);

        $runId = StorageCleanupStatus::queue();
        (new RotateScreenshots($runId))->handle();

        $this->assertSame(StorageCleanupStatus::COMPLETED, StorageCleanupStatus::get()['status']);
        $this->assertNotNull(StorageCleanupStatus::get()['requested_at']);
    }

    public function test_job_has_its_own_timeout_below_retry_after(): void
    {
        $job = new RotateScreenshots('run');

        $this->assertSame(1, $job->tries);
        $this->assertTrue($job->failOnTimeout);
        $this->assertLessThan(config('queue.connections.database.retry_after'), $job->timeout);
        // A run the worker has not killed yet is never reported as dead
        $this->assertGreaterThan($job->timeout, config('cleaner.running_timeout'));
    }

    /**
     * The web container and the queue worker are separate processes: the status written by the worker
     * must be read by the web process through the shared cache store.
     */
    public function test_status_written_by_another_process_is_read_by_the_web_process(): void
    {
        Queue::fake();
        config(['cache.default' => 'file']);
        $admin = $this->admin();

        try {
            $this->actingAs($admin)->postJson(self::URI)->assertNoContent();

            // Never needed: the worker process does not touch real screenshots
            $result = Process::path(base_path())
                ->env([
                    'CACHE_DRIVER' => 'file',
                    'CACHE_STORE' => 'file',
                    'CLEANER_THRESHOLD_USED_SPACE_PERCENTAGE' => '101',
                ])
                ->timeout(120)
                ->run([PHP_BINARY, 'artisan', 'cattr:screenshots:rotate']);

            $this->assertTrue($result->successful(), $result->errorOutput() . $result->output());

            $this->actingAs($admin)->getJson(self::URI)
                ->assertJsonPath('data.thinning.status', StorageCleanupStatus::SKIPPED)
                ->assertJsonPath('data.thinning.now', false);
        } finally {
            Cache::store('file')->forget('storage_cleanup:status');
            Cache::store('file')->forget('storage_cleanup:last');
        }
    }
}
