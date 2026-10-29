<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Enums\Role;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class PutScreenshotAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake();
        Queue::fake();
    }

    public static function uploaders(): array
    {
        return [
            'owner' => [null, true],
            'admin' => [static fn (Task $task) => UserFactory::asAdmin()->withTokens()->create(), true],
            'manager' => [static fn (Task $task) => UserFactory::asManager()->withTokens()->create(), true],
            'project manager' => [
                static function (Task $task) {
                    $user = UserFactory::withTokens()->create();
                    $user->projects()->attach($task->project_id, ['role_id' => Role::MANAGER->value]);

                    return $user;
                },
                true,
            ],
            'another user' => [static fn (Task $task) => UserFactory::withTokens()->create(), false],
            'another project member' => [
                static function (Task $task) {
                    $user = UserFactory::withTokens()->create();
                    $user->projects()->attach($task->project_id, ['role_id' => Role::USER->value]);

                    return $user;
                },
                false,
            ],
            'auditor' => [static fn (Task $task) => UserFactory::asAuditor()->withTokens()->create(), false],
        ];
    }

    #[DataProvider('uploaders')]
    public function test_only_authorized_users_can_upload_interval_screenshots(
        ?callable $createUploader,
        bool $allowed,
    ): void {
        $owner = UserFactory::withTokens()->create();
        $task = TaskFactory::forUser($owner)->create();
        $interval = IntervalFactory::forUser($owner)->forTask($task)->create();
        $uploader = $createUploader === null ? $owner : $createUploader($task);

        $response = $this->actingAs($uploader)->post(
            "time-intervals/{$interval->id}/screenshot",
            ['screenshot' => UploadedFile::fake()->image('screenshot.png', 640, 480)],
            ['Accept' => 'application/json'],
        );

        $path = app(ScreenshotService::class)->getScreenshotPath($interval);
        if ($allowed) {
            $response->assertStatus(self::HTTP_NO_CONTENT);
            Storage::assertExists($path);
            Queue::assertPushed(GenerateScreenshotThumbnail::class);
        } else {
            $response->assertStatus(self::HTTP_FORBIDDEN);
            Storage::assertMissing($path);
            $this->assertSame([], Storage::disk('local')->allFiles(), 'Unauthorized uploads must not store files');
            Queue::assertNotPushed(GenerateScreenshotThumbnail::class);
        }
    }
}
