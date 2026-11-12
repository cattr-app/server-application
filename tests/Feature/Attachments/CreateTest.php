<?php

namespace Tests\Feature\Attachments;

use App\Contracts\AttachmentService as AttachmentServiceContract;
use App\Enums\AttachmentStatus;
use App\Helpers\AttachmentHelper;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'attachment';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentService::DISK);
    }

    public static function roles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->postJson(self::URI, ['attachment' => UploadedFile::fake()->create('notes.txt', 1)])
            ->assertUnauthorized();

        $this->assertDatabaseCount((new Attachment)->getTable(), 0);
    }

    #[DataProvider('roles')]
    public function test_every_role_can_upload_a_file(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();
        $file = UploadedFile::fake()->createWithContent('notes.txt', 'attachment contents');

        $response = $this->actingAs($user)->postJson(self::URI, ['attachment' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.original_name', 'notes.txt')
            ->assertJsonPath('data.extension', 'txt')
            ->assertJsonPath('data.size', strlen('attachment contents'))
            ->assertJsonPath('data.status', AttachmentStatus::NOT_ATTACHED->value)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['id', 'mime_type', 'created_at']]);

        $this->assertDatabaseHas((new Attachment)->getTable(), [
            'id' => $response->json('data.id'),
            'original_name' => 'notes.txt',
            'user_id' => $user->id,
            'status' => AttachmentStatus::NOT_ATTACHED->value,
        ]);
    }

    public function test_the_file_is_stored_on_the_attachments_disk(): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();
        $file = UploadedFile::fake()->createWithContent('notes.txt', 'attachment contents');

        $response = $this->actingAs($user)->postJson(self::URI, ['attachment' => $file])->assertOk();

        $attachment = Attachment::findOrFail($response->json('data.id'));
        $disk = Storage::disk(AttachmentService::DISK);
        $path = app(AttachmentServiceContract::class)->getPath($attachment);

        $disk->assertExists($path);
        $this->assertSame('attachment contents', $disk->get($path));
    }

    public function test_the_attachment_field_is_required(): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->actingAs($user)->postJson(self::URI, [])
            ->assertValidationError();

        $this->assertDatabaseCount((new Attachment)->getTable(), 0);
    }

    public function test_the_attachment_must_be_a_file(): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->actingAs($user)->postJson(self::URI, ['attachment' => 'not a file'])
            ->assertValidationError();

        $this->assertDatabaseCount((new Attachment)->getTable(), 0);
    }

    public function test_a_file_over_the_upload_limit_is_rejected(): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();
        $limit = (int)AttachmentHelper::getMaxAllowedFileSize();
        // The rule takes the limit in kilobytes; the fake file only reports its size, no bytes are written
        $file = UploadedFile::fake()->create('big.bin', $limit + 1);

        $this->actingAs($user)->postJson(self::URI, ['attachment' => $file])
            ->assertValidationError();

        $this->assertDatabaseCount((new Attachment)->getTable(), 0);
    }
}
