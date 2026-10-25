<?php

namespace Tests\Feature\Attachments;

use App\Console\Commands\VerifyAttachments;
use App\Enums\AttachmentStatus;
use App\Jobs\VerifyAttachmentHash;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * The unique lock of VerifyAttachmentHash is taken by the dispatcher and released by the worker.
 * A lock that is never released (worker crash, or a worker using another cache store) must expire,
 * otherwise every later verification of the attachment is silently dropped (#409).
 */
class VerifyAttachmentHashUniqueLockTest extends TestCase
{
    private const CONTENT = 'attachment contents';

    private Attachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentService::DISK);

        $user = UserFactory::asAdmin()->create();
        $task = TaskFactory::forUser($user)->create();

        $this->attachment = Attachment::create([
            'attachmentable_id' => $task->id,
            'attachmentable_type' => $task::class,
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => strlen(self::CONTENT),
            'project_id' => $task->project_id,
            'user_id' => $user->id,
            'status' => AttachmentStatus::PROCESSING,
        ]);

        Storage::disk(AttachmentService::DISK)->put(
            "projects/{$task->project_id}/{$this->attachment->id}",
            self::CONTENT
        );
    }

    private function uniqueFor(): int
    {
        return (new VerifyAttachmentHash($this->attachment))->uniqueFor ?? 0;
    }

    private function lockKey(): string
    {
        return UniqueLock::getKey(new VerifyAttachmentHash($this->attachment));
    }

    public function test_dispatch_is_skipped_while_lock_is_held(): void
    {
        Queue::fake();

        // A job for this attachment is in flight: its lock is held.
        $this->assertTrue((new UniqueLock(Cache::store()))->acquire(new VerifyAttachmentHash($this->attachment)));

        VerifyAttachmentHash::dispatch($this->attachment);

        Queue::assertNotPushed(VerifyAttachmentHash::class);
    }

    public function test_stale_lock_expires_after_unique_for(): void
    {
        $this->freezeSecond();
        Queue::fake();

        // The worker never runs, so nothing releases the lock taken by this dispatch.
        VerifyAttachmentHash::dispatch($this->attachment);
        Queue::assertPushed(VerifyAttachmentHash::class, 1);

        VerifyAttachmentHash::dispatch($this->attachment);
        Queue::assertPushed(VerifyAttachmentHash::class, 1);

        $this->travel($this->uniqueFor() - 1)->seconds();
        VerifyAttachmentHash::dispatch($this->attachment);
        Queue::assertPushed(VerifyAttachmentHash::class, 1);

        $this->travel(1)->seconds();
        VerifyAttachmentHash::dispatch($this->attachment);
        Queue::assertPushed(VerifyAttachmentHash::class, 2);
    }

    public function test_next_daily_run_queues_attachment_with_stale_lock(): void
    {
        Queue::fake();

        $this->artisan(VerifyAttachments::class)->assertSuccessful();
        Queue::assertPushed(
            VerifyAttachmentHash::class,
            fn (VerifyAttachmentHash $job) => $job->attachment->is($this->attachment)
        );
        $pushed = Queue::pushed(VerifyAttachmentHash::class)->count();

        $this->travel(1)->days();

        $this->artisan(VerifyAttachments::class)->assertSuccessful();
        Queue::assertPushed(VerifyAttachmentHash::class, $pushed * 2);
    }

    public function test_lock_is_released_after_processing(): void
    {
        // Sync queue: the job runs right away and the worker side releases the lock.
        VerifyAttachmentHash::dispatch($this->attachment);

        $this->assertSame(AttachmentStatus::GOOD, $this->attachment->refresh()->status);
        $this->assertTrue(Cache::lock($this->lockKey(), 10)->get());
    }

    public function test_unique_for_is_shorter_than_daily_run_interval(): void
    {
        $this->assertGreaterThan(0, $this->uniqueFor());
        $this->assertLessThan(86400, $this->uniqueFor());
    }
}
