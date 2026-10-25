<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\TaskFactory;
use Tests\TestCase;

/**
 * Offline screenshot archives are processed within the request, so an archive that cannot be processed in time
 * (too many entries, too large unpacked) or whose entries are not plain file names (Zip Slip) is rejected
 * before anything is extracted or attached.
 */
class OfflineScreenshotsArchiveLimitsTest extends TestCase
{
    use UploadsOfflineScreenshots;

    private ScreenshotService $screenshotService;
    private User $user;
    private TimeInterval $interval;
    private string $screenshotId;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->screenshotService = app(ScreenshotService::class);

        $this->user = $this->createOfflineAdmin();
        $this->interval = $this->createOfflineInterval($this->user, TaskFactory::forUser($this->user)->create());
        $this->screenshotId = $this->interval->screenshot_id;
    }

    public function test_default_limits(): void
    {
        $this->assertSame(250, config('app.offline_screenshots.max_files'));
        $this->assertSame(1024 * 1024 * 1024, config('app.offline_screenshots.max_size'));
    }

    public function test_archive_within_the_limits_is_attached(): void
    {
        config(['app.offline_screenshots.max_files' => 2]);

        $response = $this->uploadOfflineArchive($this->user, $this->validEntry() + [
            "{$this->user->id}_" . Str::uuid() => $this->image(),
        ]);

        $response->assertOk()->assertJsonCount(2, 'data');
        $result = collect($response->json('data'))->firstWhere('screenshot_id', $this->screenshotId);
        $this->assertTrue($result['success']);
        $this->interval->refresh();
        $this->assertNull($this->interval->screenshot_id);
        Storage::assertExists($this->screenshotService->getScreenshotPath($this->interval));
        $this->assertTmpIsEmpty();
    }

    public function test_archive_with_too_many_entries_is_rejected(): void
    {
        config(['app.offline_screenshots.max_files' => 2]);

        $entries = $this->validEntry();
        for ($i = 0; $i < 2; $i++) {
            $entries["{$this->user->id}_" . Str::uuid()] = $this->image();
        }

        $response = $this->uploadOfflineArchive($this->user, $entries);

        $this->assertRejected(
            $response,
            __('validation.offline-sync.too_many_files', ['count' => 3, 'max' => 2]),
        );
    }

    public function test_archive_too_large_when_unpacked_is_rejected(): void
    {
        config(['app.offline_screenshots.max_size' => 1024 * 1024]);

        // ~1.5 MiB unpacked, a few KiB packed
        $entries = $this->validEntry();
        for ($i = 0; $i < 3; $i++) {
            $entries["{$this->user->id}_" . Str::uuid()] = str_repeat("\0", 512 * 1024);
        }

        $response = $this->uploadOfflineArchive($this->user, $entries);

        $this->assertRejected($response, __('validation.offline-sync.too_large', ['max' => 1]));
    }

    public static function unsafeEntryNames(): array
    {
        return [
            'parent directory (Zip Slip)' => ['../evil.jpg'],
            'nested parent directory (Zip Slip)' => ['screenshots/../../evil.jpg'],
            'absolute path' => ['/tmp/evil.jpg'],
            'windows parent directory' => ['..\\evil.jpg'],
            'windows absolute path' => ['C:\\evil.jpg'],
            'windows drive-relative path' => ['C:evil.jpg'],
            'directory' => ['screenshots/1_00000000-0000-0000-0000-000000000000'],
        ];
    }

    #[DataProvider('unsafeEntryNames')]
    public function test_archive_with_an_unsafe_entry_name_is_rejected(string $name): void
    {
        $response = $this->uploadOfflineArchive($this->user, $this->validEntry() + [$name => $this->image()]);

        $this->assertRejected($response, __('validation.offline-sync.wrong_file_name'));
    }

    /**
     * @return array<string, string> A screenshot that would be attached to the interval if the archive were accepted
     */
    private function validEntry(): array
    {
        return ["{$this->user->id}_$this->screenshotId" => $this->image()];
    }

    private function image(): string
    {
        return UploadedFile::fake()->image('s.png', 64, 48)->getContent();
    }

    private function assertRejected(TestResponse $response, string $message): void
    {
        $response->assertStatus(self::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString($message, $response->getContent());

        $this->interval->refresh();
        $this->assertSame($this->screenshotId, $this->interval->screenshot_id, 'The screenshot was attached');
        Storage::assertMissing($this->screenshotService->getScreenshotPath($this->interval));
        Queue::assertNotPushed(GenerateScreenshotThumbnail::class);
        $this->assertTmpIsEmpty();
    }
}
