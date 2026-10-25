<?php

namespace Tests\Feature\Attachments;

use App\Enums\AttachmentStatus;
use App\Models\Attachment;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TemporaryUrlTest extends TestCase
{
    private const CONTENT = 'attachment contents';

    private User $admin;
    private Attachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentService::DISK);

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $task = TaskFactory::forUser($this->admin)->create();

        $this->attachment = Attachment::create([
            'attachmentable_id' => $task->id,
            'attachmentable_type' => $task::class,
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => strlen(self::CONTENT),
            'project_id' => $task->project_id,
            'user_id' => $this->admin->id,
            'status' => AttachmentStatus::GOOD,
        ]);

        Storage::disk(AttachmentService::DISK)->put(
            "projects/{$task->project_id}/{$this->attachment->id}",
            self::CONTENT
        );
    }

    public function test_seconds_from_query_string(): void
    {
        $this->freezeSecond();

        $response = $this->actingAs($this->admin)
            ->getJson("attachment/{$this->attachment->id}/temporary-url?seconds=30");

        $response->assertOk();

        $url = $response->getContent();
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith("/api/tmp-attachment-link/{$this->attachment->id}?", $url);
        $this->assertSame(now()->addSeconds(30)->getTimestamp(), (int)$query['expires']);

        $this->get($url)->assertOk()->assertStreamedContent(self::CONTENT);
    }

    public function test_default_lifetime(): void
    {
        $this->freezeSecond();

        $response = $this->actingAs($this->admin)->getJson("attachment/{$this->attachment->id}/temporary-url");

        $response->assertOk();

        parse_str((string)parse_url($response->getContent(), PHP_URL_QUERY), $query);

        $this->assertSame(now()->addHour()->getTimestamp(), (int)$query['expires']);
    }
}
