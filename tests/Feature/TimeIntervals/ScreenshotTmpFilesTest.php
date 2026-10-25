<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Enums\Role;
use App\Enums\ScreenshotsState;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Settings;
use Tests\Facades\IntervalFactory;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Uploaded screenshots must be processed within the request and leave nothing in storage/app/tmp.
 *
 * The queue is faked: the worker may run in another container without access to the local disk of the web
 * container, so nothing that reads local temporary files may depend on queued jobs.
 */
class ScreenshotTmpFilesTest extends TestCase
{
    use UploadsOfflineScreenshots;

    private const CREATE_URI = 'time-intervals/create';

    private ScreenshotService $screenshotService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->screenshotService = app(ScreenshotService::class);
    }

    public static function screenshotStates(): array
    {
        return [
            'project required' => [ScreenshotsState::REQUIRED, ScreenshotsState::FORBIDDEN, true],
            'project optional, user required' => [ScreenshotsState::OPTIONAL, ScreenshotsState::REQUIRED, true],
            'project optional, user forbidden' => [ScreenshotsState::OPTIONAL, ScreenshotsState::FORBIDDEN, false],
            'project forbidden' => [ScreenshotsState::FORBIDDEN, ScreenshotsState::REQUIRED, false],
        ];
    }

    #[DataProvider('screenshotStates')]
    public function test_create_with_screenshot_leaves_no_tmp_files(
        ScreenshotsState $projectState,
        ScreenshotsState $userState,
        bool $kept,
    ): void {
        // InitialSeeder makes screenshots required globally, which would override the project state.
        Settings::scope('core')->set('screenshots_state', ScreenshotsState::ANY->value);

        $admin = UserFactory::asAdmin()->withTokens()->create();
        $admin->update(['screenshots_state' => $userState]);
        $task = $this->createTask($admin, $projectState);

        $this->postInterval($admin, $task)->assertOk();

        $interval = TimeInterval::whereUserId($admin->id)->whereTaskId($task->id)->sole();
        $screenshotPath = $this->screenshotService->getScreenshotPath($interval);
        $kept ? Storage::assertExists($screenshotPath) : Storage::assertMissing($screenshotPath);

        $this->assertTmpIsEmpty();
    }

    public function test_failed_interval_creation_leaves_no_tmp_files(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $task = $this->createTask($admin, ScreenshotsState::REQUIRED);

        TimeInterval::creating(static fn () => throw new RuntimeException('Interval creation failed'));

        $this->postInterval($admin, $task)->assertStatus(self::HTTP_INTERNAL_SERVER_ERROR);

        $this->assertSame(0, TimeInterval::whereUserId($admin->id)->count());
        $this->assertTmpIsEmpty();
    }

    public function test_offline_screenshots_are_attached_within_the_request(): void
    {
        $user = UserFactory::withTokens()->create();
        $task = TaskFactory::forUser($user)->create();
        $interval = $this->createOfflineInterval($user, $task);

        $response = $this->uploadOfflineArchive($user, [
            "{$user->id}_$interval->screenshot_id" => UploadedFile::fake()->image('s.png', 640, 480)->getContent(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.success', true)
            ->assertJsonPath('data.0.message', __('validation.offline-sync.screenshot_attached'));

        $interval->refresh();
        $this->assertNull($interval->screenshot_id);
        Storage::assertExists($this->screenshotService->getScreenshotPath($interval));
        Queue::assertPushed(GenerateScreenshotThumbnail::class);

        $this->assertTmpIsEmpty();
    }

    public static function userIds(): array
    {
        return [
            'single digit' => [7],
            'two digits' => [57],
            'four digits' => [1234],
        ];
    }

    /**
     * The whole number before "_" is the user ID: "57_<uuid>" belongs to user 57, not to user 7.
     */
    #[DataProvider('userIds')]
    public function test_offline_screenshot_is_matched_by_the_whole_user_id(int $userId): void
    {
        $user = $this->createUserWithId($userId);
        $task = TaskFactory::forUser($user)->create();
        $interval = $this->createOfflineInterval($user, $task);

        $response = $this->uploadOfflineArchive($user, [
            "{$userId}_$interval->screenshot_id" => UploadedFile::fake()->image('s.png', 640, 480)->getContent(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.success', true)
            ->assertJsonPath('data.0.user_id', $userId)
            ->assertJsonPath('data.0.screenshot_id', $interval->screenshot_id);

        $interval->refresh();
        $this->assertNull($interval->screenshot_id);
        Storage::assertExists($this->screenshotService->getScreenshotPath($interval));

        $this->assertTmpIsEmpty();
    }

    public static function wrongFileNames(): array
    {
        return [
            'no user id' => ['_' . Str::uuid()],
            'not a number' => ['abc_' . Str::uuid()],
            'prefix before the user id' => ['x57_' . Str::uuid()],
            'not a uuid' => ['57_not-a-uuid'],
            'suffix after the uuid' => ['57_' . Str::uuid() . '.png'],
            'no separator' => ['not-a-screenshot.txt'],
            'trailing newline after the uuid' => ['57_' . Str::uuid() . "\n"],
        ];
    }

    #[DataProvider('wrongFileNames')]
    public function test_offline_archive_with_a_wrong_file_name_is_rejected(string $fileName): void
    {
        $user = UserFactory::withTokens()->create();

        $this->uploadOfflineArchive($user, [$fileName => 'foo'])
            ->assertStatus(self::HTTP_BAD_REQUEST)
            ->assertJsonPath('error.message', __('Wrong screenshot file name'));

        $this->assertTmpIsEmpty();
    }

    public function test_broken_offline_screenshot_is_reported_as_not_attached(): void
    {
        $user = UserFactory::withTokens()->create();
        $task = TaskFactory::forUser($user)->create();
        $interval = $this->createOfflineInterval($user, $task);
        $screenshotId = $interval->screenshot_id;

        $response = $this->uploadOfflineArchive($user, [
            "{$user->id}_$screenshotId" => 'this is not an image',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.success', false)
            ->assertJsonPath('data.0.message', __('validation.offline-sync.screenshot_not_attached'));

        $interval->refresh();
        $this->assertSame($screenshotId, $interval->screenshot_id);
        Storage::assertMissing($this->screenshotService->getScreenshotPath($interval));

        $this->assertTmpIsEmpty();
    }

    /**
     * The uploader may attach a screenshot only to an interval they may create (the offline intervals upload rule).
     *
     * @return array<string, array{callable(Task): User, bool}>
     */
    public static function uploaders(): array
    {
        return [
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
            'another member of the project' => [
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
    public function test_offline_screenshot_of_another_user_is_attached_only_with_rights_on_the_interval(
        callable $createUploader,
        bool $attached,
    ): void {
        $owner = UserFactory::withTokens()->create();
        $task = TaskFactory::forUser($owner)->create();
        $interval = $this->createOfflineInterval($owner, $task);
        $screenshotId = $interval->screenshot_id;
        $uploader = $createUploader($task);

        $response = $this->uploadOfflineArchive($uploader, [
            "{$owner->id}_$screenshotId" => UploadedFile::fake()->image('s.png', 640, 480)->getContent(),
        ]);

        $response->assertOk()->assertJsonPath('data.0.success', $attached);

        $interval->refresh();
        if ($attached) {
            $response->assertJsonPath('data.0.message', __('validation.offline-sync.screenshot_attached'));
            $this->assertNull($interval->screenshot_id);
            Storage::assertExists($this->screenshotService->getScreenshotPath($interval));
        } else {
            // Reported exactly like a missing interval, without the interval data
            $response->assertJsonPath('data.0.message', __('validation.offline-sync.cannot_find_interval'))
                ->assertJsonPath('data.0.interval', null);
            $this->assertSame($screenshotId, $interval->screenshot_id);
            Storage::assertMissing($this->screenshotService->getScreenshotPath($interval));
            $this->assertSame([], Storage::disk('local')->allFiles(), 'Nothing must be stored');
            Queue::assertNotPushed(GenerateScreenshotThumbnail::class);
        }

        $this->assertTmpIsEmpty();
    }

    private function createTask(User $user, ScreenshotsState $projectState): Task
    {
        $project = ProjectFactory::create();
        $project->update(['screenshots_state' => $projectState]);

        return TaskFactory::forUser($user)->forProject($project)->create();
    }

    private function postInterval(User $user, Task $task): \Illuminate\Testing\TestResponse
    {
        $intervalData = IntervalFactory::createRandomModelData();
        $intervalData['task_id'] = $task->id;
        $intervalData['user_id'] = $user->id;

        return $this->actingAs($user)->post(
            self::CREATE_URI,
            $intervalData + ['screenshot' => UploadedFile::fake()->image('screenshot.png', 320, 240)],
            ['Accept' => 'application/json'],
        );
    }
}
