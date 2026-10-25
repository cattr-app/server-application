<?php

namespace Tests\Feature\Attachments;

use App\Enums\AttachmentStatus;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

/**
 * GET api/tmp-attachment-link/{attachment} is protected by the "signed" middleware only.
 */
class TemporaryDownloadTest extends TestCase
{
    private const ROUTE = 'attachment.temporary-download';
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
            'status' => AttachmentStatus::GOOD,
        ]);

        Storage::disk(AttachmentService::DISK)->put(
            "projects/{$task->project_id}/{$this->attachment->id}",
            self::CONTENT
        );
    }

    public function test_valid_link(): void
    {
        $url = URL::temporarySignedRoute(self::ROUTE, now()->addMinute(), $this->attachment);

        $this->get($url)->assertOk()->assertStreamedContent(self::CONTENT);
    }

    public function test_unsigned_link(): void
    {
        $url = route(self::ROUTE, $this->attachment);

        $this->assertInvalidSignature($this->getJson($url));
    }

    public function test_tampered_link(): void
    {
        $url = URL::temporarySignedRoute(self::ROUTE, now()->addMinute(), $this->attachment);

        $this->assertInvalidSignature(
            $this->getJson(preg_replace('/expires=\d+/', 'expires=' . now()->addYear()->getTimestamp(), $url))
        );
    }

    public function test_expired_link(): void
    {
        $url = URL::temporarySignedRoute(self::ROUTE, now()->subSecond(), $this->attachment);

        $this->assertInvalidSignature($this->getJson($url));
    }

    /** Same body as every other 403 of the API (see AuthorizationException::ERROR_TYPE_FORBIDDEN). */
    private function assertInvalidSignature(TestResponse $response): void
    {
        $response->assertForbidden()
            ->assertJsonPath('status', self::HTTP_FORBIDDEN)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.message', 'Invalid signature.');
    }
}
