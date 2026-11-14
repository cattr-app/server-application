<?php

namespace Tests\Feature\TimeIntervals;

use App\Jobs\ProcessOfflineScreenshotsUpload;
use App\Models\OfflineScreenshotUpload;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\Factories\UserFactory as UserFactoryInstance;
use ZipArchive;

/**
 * Helpers for offline-sync/upload-screenshots: an archive of `<user_id>_<screenshot_id>` entries that is stored by the
 * request and attached by a queue job. The tests fake the queue and run the job explicitly.
 */
trait UploadsOfflineScreenshots
{
    /** @var array<int, string> API tokens of the users created by these helpers, by user ID */
    private array $tokens = [];

    private function createOfflineInterval(User $user, Task $task): TimeInterval
    {
        $interval = IntervalFactory::forUser($user)->forTask($task)->create();
        $interval->update(['screenshot_id' => (string)Str::uuid()]);

        return $interval;
    }

    /**
     * Creates a regular user with the given ID, to cover user IDs of different lengths.
     */
    private function createUserWithId(int $id): User
    {
        if (User::whereKey($id)->exists()) {
            $this->markTestSkipped("User ID $id is taken.");
        }

        $user = User::forceCreate(['id' => $id] + UserFactory::createRandomModelData());
        $this->tokens[$id] = $user->createToken('testing-token', ['*'], now()->addDay())->plainTextToken;

        return $user;
    }

    private function createOfflineAdmin(): User
    {
        $user = User::forceCreate(
            ['role_id' => UserFactoryInstance::ADMIN_ROLE] + UserFactory::createRandomModelData()
        );
        $this->tokens[$user->id] = $user->createToken('testing-token', ['*'], now()->addDay())->plainTextToken;

        return $user;
    }

    /**
     * @param array<string, string> $entries File name => content
     */
    private function uploadOfflineArchive(User $user, array $entries): TestResponse
    {
        return $this->uploadOfflineArchiveFile($user, $this->createOfflineArchive($entries));
    }

    /**
     * @param array<string, string> $entries File name => content
     * @return string Path of the archive
     */
    private function createOfflineArchive(array $entries): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'offline');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $zipPath;
    }

    private function uploadOfflineArchiveFile(User $user, string $zipPath): TestResponse
    {
        return $this->actingAs($this->tokens[$user->id] ?? $user)->post(
            'offline-sync/upload-screenshots',
            ['file' => new UploadedFile($zipPath, 'screenshots.cattr', 'application/zip', null, true)],
            ['Accept' => 'application/json'],
        );
    }

    /**
     * Uploads the archive, runs the queued job like a worker would and returns the polled upload.
     *
     * @param array<string, string> $entries File name => content
     */
    private function uploadAndProcessOfflineArchive(User $user, array $entries): TestResponse
    {
        $this->uploadOfflineArchive($user, $entries)->assertStatus(self::HTTP_ACCEPTED);

        return $this->processOfflineUpload($user, OfflineScreenshotUpload::query()->latest('id')->firstOrFail());
    }

    private function processOfflineUpload(User $user, OfflineScreenshotUpload $upload): TestResponse
    {
        $this->runOfflineUploadJob($upload);

        return $this->pollOfflineUpload($user, $upload->id);
    }

    private function runOfflineUploadJob(OfflineScreenshotUpload $upload): void
    {
        $job = new ProcessOfflineScreenshotsUpload($upload);
        app()->call([$job, 'handle']);
    }

    private function pollOfflineUpload(User $user, int $uploadId): TestResponse
    {
        // Several users read within one test: the guard keeps the user of the previous request otherwise
        $this->app['auth']->forgetGuards();

        return $this->actingAs($this->tokens[$user->id] ?? $user)
            ->get("offline-sync/uploads/$uploadId", ['Accept' => 'application/json']);
    }

    /**
     * @return array<int, string> Stored archives on the default disk
     */
    private function storedOfflineArchives(): array
    {
        return Storage::disk(config('filesystems.default'))->allFiles(OfflineScreenshotUpload::ARCHIVE_DIRECTORY);
    }

    private function assertNoStoredArchive(): void
    {
        $this->assertSame([], $this->storedOfflineArchives(), 'Archives left on the default disk');
    }

    private function assertTmpIsEmpty(): void
    {
        $this->assertSame([], Storage::disk('local')->allFiles('tmp'), 'Files left in storage/app/tmp');
        $this->assertSame([], Storage::disk('local')->directories('tmp'), 'Directories left in storage/app/tmp');
    }
}
