<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Enums\ScreenshotsState;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
use Tests\Factories\UserFactory as UserFactoryInstance;
use Tests\TestCase;
use ZipArchive;

/**
 * Uploaded screenshots must be processed within the request and leave nothing in storage/app/tmp.
 *
 * The queue is faked: the worker may run in another container without access to the local disk of the web
 * container, so nothing that reads local temporary files may depend on queued jobs.
 */
class ScreenshotTmpFilesTest extends TestCase
{
    private const CREATE_URI = 'time-intervals/create';
    private const OFFLINE_URI = 'offline-sync/upload-screenshots';

    private ScreenshotService $screenshotService;
    private ?string $offlineToken = null;

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
        $user = $this->createSingleDigitIdAdmin();
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

    public function test_broken_offline_screenshot_is_reported_as_not_attached(): void
    {
        $user = $this->createSingleDigitIdAdmin();
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

    public function test_offline_archive_with_a_wrong_file_name_leaves_no_tmp_files(): void
    {
        $user = UserFactory::asAdmin()->withTokens()->create();

        $this->uploadOfflineArchive($user, ['not-a-screenshot.txt' => 'foo'])
            ->assertStatus(self::HTTP_BAD_REQUEST);

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

    private function createOfflineInterval(User $user, Task $task): TimeInterval
    {
        $interval = IntervalFactory::forUser($user)->forTask($task)->create();
        $interval->update(['screenshot_id' => (string)Str::uuid()]);

        return $interval;
    }

    /**
     * The controller takes the user ID from the screenshot file name with /\d_.+/, which keeps only its last
     * digit, so the archive can be matched only for users with a single-digit ID.
     */
    private function createSingleDigitIdAdmin(): User
    {
        $takenIds = DB::table('users')->whereBetween('id', [1, 9])->pluck('id')->all();
        $freeIds = array_values(array_diff(range(1, 9), $takenIds));
        if ($freeIds === []) {
            $this->markTestSkipped('No free single-digit user ID.');
        }

        $user = User::forceCreate(
            ['id' => $freeIds[0], 'role_id' => UserFactoryInstance::ADMIN_ROLE] + UserFactory::createRandomModelData()
        );
        $this->offlineToken = $user->createToken('testing-token', ['*'], now()->addDay())->plainTextToken;

        return $user;
    }

    /**
     * @param array<string, string> $entries File name => content
     */
    private function uploadOfflineArchive(User $user, array $entries): \Illuminate\Testing\TestResponse
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'offline');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $this->actingAs($this->offlineToken ?? $user)->post(
            self::OFFLINE_URI,
            ['file' => new UploadedFile($zipPath, 'screenshots.cattr', 'application/zip', null, true)],
            ['Accept' => 'application/json'],
        );
    }

    private function assertTmpIsEmpty(): void
    {
        $this->assertSame([], Storage::disk('local')->allFiles('tmp'), 'Files left in storage/app/tmp');
        $this->assertSame([], Storage::disk('local')->directories('tmp'), 'Directories left in storage/app/tmp');
    }
}
