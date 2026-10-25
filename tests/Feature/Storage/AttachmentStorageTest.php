<?php

namespace Tests\Feature\Storage;

use App\Contracts\AttachmentService;
use App\Enums\AttachmentStatus;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\SusFiles;
use App\Models\Task;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class AttachmentStorageTest extends TestCase
{
    private User $admin;
    private Attachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        /** @var Task $task */
        $task = TaskFactory::forUser($this->admin)->create();

        $this->attachment = Attachment::create([
            'attachmentable_id' => $task->id,
            'attachmentable_type' => Task::class,
            'original_name' => 'report final.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 4,
            'hash' => '',
            'project_id' => $task->project_id,
            'user_id' => $this->admin->id,
            'status' => AttachmentStatus::GOOD,
        ]);
    }

    private function useS3(): void
    {
        config(['filesystems.disks.attachments.driver' => 's3']);
        Storage::fake('attachments');
        Storage::disk('attachments')->put(app(AttachmentService::class)->getPath($this->attachment), '%PDF');
    }

    private function temporaryUrl(int $seconds): string
    {
        $response = $this->actingAs($this->admin)
            ->get("attachment/{$this->attachment->id}/temporary-url?seconds=$seconds");
        $response->assertOk();

        return $response->getContent();
    }

    public function test_local_disk_uses_signed_route(): void
    {
        Storage::fake('attachments');

        $url = $this->temporaryUrl(30);

        $this->assertStringStartsWith("/api/tmp-attachment-link/{$this->attachment->id}?expires=", $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_s3_disk_uses_presigned_url(): void
    {
        $this->useS3();
        $this->freezeSecond();

        $url = $this->temporaryUrl(3600);

        $expiration = now()->addHour()->getTimestamp();
        // Storage::fake() builds presigned-like URLs as <path>?expiration=<timestamp>
        $this->assertStringEndsWith(
            app(AttachmentService::class)->getPath($this->attachment) . "?expiration=$expiration",
            $url,
        );
    }

    public function test_s3_disk_presigns_up_to_seven_days(): void
    {
        $this->useS3();
        $this->freezeSecond();

        $url = $this->temporaryUrl(AttachmentService::MAX_TEMPORARY_URL_SECONDS);

        $this->assertSame(604800, AttachmentService::MAX_TEMPORARY_URL_SECONDS);
        $this->assertStringEndsWith('?expiration=' . now()->addDays(7)->getTimestamp(), $url);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function invalidLifetimes(): array
    {
        return [
            'local, longer than 7 days' => ['local', AttachmentService::MAX_TEMPORARY_URL_SECONDS + 1],
            's3, longer than 7 days' => ['s3', AttachmentService::MAX_TEMPORARY_URL_SECONDS + 1],
            'local, 14 days' => ['local', 60 * 60 * 24 * 14],
            'local, zero' => ['local', 0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidLifetimes')]
    public function test_lifetime_is_limited_to_seven_days_on_every_disk(string $driver, int $seconds): void
    {
        $driver === 's3' ? $this->useS3() : Storage::fake('attachments');

        $this->actingAs($this->admin)
            ->getJson("attachment/{$this->attachment->id}/temporary-url?seconds=$seconds")
            ->assertValidationError();
    }

    /**
     * @return array<string, array{string, AttachmentStatus}>
     */
    public static function notDownloadableStatuses(): array
    {
        return [
            'local, processing' => ['local', AttachmentStatus::PROCESSING],
            'local, bad' => ['local', AttachmentStatus::BAD],
            's3, processing' => ['s3', AttachmentStatus::PROCESSING],
            's3, bad' => ['s3', AttachmentStatus::BAD],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notDownloadableStatuses')]
    public function test_temporary_url_is_issued_only_for_good_attachments(
        string $driver,
        AttachmentStatus $status
    ): void {
        $this->attachment->update(['status' => $status]);
        $driver === 's3' ? $this->useS3() : Storage::fake('attachments');

        $this->actingAs($this->admin)
            ->get("attachment/{$this->attachment->id}/temporary-url?seconds=60")
            ->assertNotFound('page_not_found');
    }

    public static function disks(): array
    {
        return ['local' => ['local'], 's3' => ['s3']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('disks')]
    public function test_temporary_url_is_issued_for_good_attachment(string $driver): void
    {
        $driver === 's3' ? $this->useS3() : Storage::fake('attachments');

        $this->assertNotSame('', $this->temporaryUrl(60));
    }

    public function test_local_signed_link_does_not_serve_attachment_that_turned_bad(): void
    {
        Storage::fake('attachments');
        Storage::disk('attachments')->put(app(AttachmentService::class)->getPath($this->attachment), '%PDF');

        $url = $this->temporaryUrl(60);
        $this->get($url)->assertOk();

        $this->attachment->update(['status' => AttachmentStatus::BAD]);
        $this->get($url)->assertNotFound('page_not_found');
    }

    public function test_presigned_url_sets_download_headers(): void
    {
        $this->useS3();
        $options = null;
        Storage::disk('attachments')->buildTemporaryUrlsUsing(
            function ($path, $expiration, $urlOptions) use (&$options) {
                $options = $urlOptions;

                return "https://bucket.example/$path";
            }
        );

        app(AttachmentService::class)->getTemporaryUrl($this->attachment, now()->addMinutes(10));

        $this->assertSame('application/pdf', $options['ResponseContentType']);
        $this->assertStringStartsWith(
            'attachment; filename="report final.pdf"',
            $options['ResponseContentDisposition']
        );
    }

    /**
     * Configure the attachments disk and store the file of the GOOD attachment, with a hash that no longer
     * matches it, so the next verifyHash() turns the attachment BAD.
     */
    private function useDiskWithTamperedFile(string $driver): void
    {
        if ($driver === 's3') {
            $this->useS3();
        } else {
            Storage::fake('attachments');
            Storage::disk('attachments')->put($this->publicKey(), '%PDF');
        }
        $this->attachment->update(['hash' => 'hash of the file as uploaded']);
    }

    private function publicKey(): string
    {
        return "projects/{$this->attachment->project_id}/{$this->attachment->id}";
    }

    private function quarantineKey(): string
    {
        return AttachmentService::QUARANTINE_DIR . '/' . $this->publicKey();
    }

    private function verify(): void
    {
        app(AttachmentService::class)->verifyHash($this->attachment);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('disks')]
    public function test_attachment_turning_bad_is_moved_to_quarantine(string $driver): void
    {
        $this->useDiskWithTamperedFile($driver);

        $this->verify();

        $this->assertSame(AttachmentStatus::BAD, $this->attachment->fresh()->status);
        Storage::disk('attachments')->assertMissing($this->publicKey());
        $this->assertSame('%PDF', Storage::disk('attachments')->get($this->quarantineKey()));
        $this->assertSame($this->quarantineKey(), app(AttachmentService::class)->getPath($this->attachment->fresh()));
    }

    public function test_presigned_url_issued_while_good_stops_resolving_after_turning_bad(): void
    {
        $this->useDiskWithTamperedFile('s3');
        $url = $this->temporaryUrl(3600);
        // Storage::fake() builds presigned-like URLs as <key>?expiration=<timestamp>: the URL points to the public key
        $this->assertStringContainsString($this->publicKey() . '?expiration=', $url);
        Storage::disk('attachments')->assertExists($this->publicKey());

        $this->verify();

        // the presigned URL is signed for this key; with no object under it S3 answers 404 NoSuchKey
        Storage::disk('attachments')->assertMissing($this->publicKey());
    }

    public function test_local_signed_link_issued_while_good_stops_serving_after_turning_bad(): void
    {
        $this->useDiskWithTamperedFile('local');
        $url = $this->temporaryUrl(3600);
        $this->get($url)->assertOk();

        $this->verify();

        $this->get($url)->assertNotFound('page_not_found');
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function failedMoves(): array
    {
        return [
            'local, move fails' => ['local', false],
            'local, copied but not deleted' => ['local', true],
            's3, move fails' => ['s3', false],
            's3, copied but not deleted' => ['s3', true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failedMoves')]
    public function test_attachment_is_not_marked_bad_while_its_file_stays_public(string $driver, bool $copies): void
    {
        $this->useDiskWithTamperedFile($driver);
        $fake = Storage::disk('attachments');
        // move() of S3 is copy + delete: either nothing happens, or the delete of the public key fails
        Storage::set('attachments', new class ($fake->getDriver(), $fake->getAdapter(), $fake->getConfig(), $copies)
            extends FilesystemAdapter {
            public function __construct(
                FilesystemOperator $driver,
                FlysystemAdapter $adapter,
                array $config,
                private readonly bool $copies
            ) {
                parent::__construct($driver, $adapter, $config);
            }

            public function move($from, $to)
            {
                return $this->copies && $this->copy($from, $to);
            }
        });

        try {
            $this->verify();
            $this->fail('The attachment has been marked BAD while its file is still public');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Unable to quarantine', $e->getMessage());
        }

        $this->assertSame(AttachmentStatus::GOOD, $this->attachment->fresh()->status);
        $this->assertSame(AttachmentStatus::GOOD, $this->attachment->status);
        Storage::disk('attachments')->assertExists($this->publicKey());
    }

    public function test_retry_after_the_file_was_quarantined_marks_attachment_bad(): void
    {
        // a previous attempt moved the file but did not save the status
        $this->useDiskWithTamperedFile('s3');
        Storage::disk('attachments')->move($this->publicKey(), $this->quarantineKey());

        $this->verify();

        $this->assertSame(AttachmentStatus::BAD, $this->attachment->fresh()->status);
        $this->assertSame('%PDF', Storage::disk('attachments')->get($this->quarantineKey()));
    }

    public function test_bad_attachment_stays_bad_and_quarantined_on_reverification(): void
    {
        $this->useDiskWithTamperedFile('s3');
        $this->verify();
        // even a hash that matches the quarantined file again does not bring it back
        $this->attachment->update(['hash' => app(AttachmentService::class)->getHashSum($this->quarantineKey())]);

        $this->verify();

        $this->assertSame(AttachmentStatus::BAD, $this->attachment->fresh()->status);
        Storage::disk('attachments')->assertMissing($this->publicKey());
        Storage::disk('attachments')->assertExists($this->quarantineKey());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('disks')]
    public function test_deleting_bad_attachment_removes_quarantined_file(string $driver): void
    {
        $this->useDiskWithTamperedFile($driver);
        $this->verify();
        Storage::disk('attachments')->assertExists($this->quarantineKey());

        app(AttachmentService::class)->deleteAttachment($this->attachment->fresh());

        $this->assertNull(Attachment::find($this->attachment->id));
        Storage::disk('attachments')->assertMissing($this->quarantineKey());
    }

    public function test_project_deletion_removes_quarantined_files(): void
    {
        $this->useDiskWithTamperedFile('s3');
        $this->verify();

        app(AttachmentService::class)->handleProjectDeletion(Project::findOrFail($this->attachment->project_id));

        $this->assertNull(Attachment::find($this->attachment->id));
        Storage::disk('attachments')->assertMissing($this->quarantineKey());
    }

    public function test_bad_attachment_stays_quarantined_when_its_task_changes_project(): void
    {
        $this->useDiskWithTamperedFile('s3');
        $this->verify();
        $oldQuarantineKey = $this->quarantineKey();
        $project = ProjectFactory::create();

        app(AttachmentService::class)->moveAttachment($this->attachment->fresh(), $project->id);

        $this->attachment->refresh();
        $this->assertSame($project->id, $this->attachment->project_id);
        Storage::disk('attachments')->assertMissing($oldQuarantineKey);
        Storage::disk('attachments')->assertMissing($this->publicKey());
        $this->assertSame('%PDF', Storage::disk('attachments')->get($this->quarantineKey()));
    }

    public function test_find_sus_files_looks_into_quarantine(): void
    {
        $this->useDiskWithTamperedFile('s3');
        $this->verify();
        $orphan = AttachmentService::QUARANTINE_DIR . "/projects/{$this->attachment->project_id}/00000000-orphan";
        Storage::disk('attachments')->put($orphan, 'orphan');

        $this->artisan('cattr:attachments:find-sus-files')->assertSuccessful();

        $this->assertSame([$orphan], SusFiles::pluck('path')->all());
    }
}
