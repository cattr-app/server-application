<?php

namespace Tests\Feature\Storage;

use App\Contracts\AttachmentService;
use App\Enums\AttachmentStatus;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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
}
