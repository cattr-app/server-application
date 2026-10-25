<?php

namespace Tests\Feature\TimeIntervals;

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
 * Helpers for offline-sync/upload-screenshots: an archive of `<user_id>_<screenshot_id>` entries.
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
        $user = User::forceCreate(['role_id' => UserFactoryInstance::ADMIN_ROLE] + UserFactory::createRandomModelData());
        $this->tokens[$user->id] = $user->createToken('testing-token', ['*'], now()->addDay())->plainTextToken;

        return $user;
    }

    /**
     * @param array<string, string> $entries File name => content
     */
    private function uploadOfflineArchive(User $user, array $entries): TestResponse
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'offline');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $this->actingAs($this->tokens[$user->id] ?? $user)->post(
            'offline-sync/upload-screenshots',
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
