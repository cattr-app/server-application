<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\TimeInterval;
use App\Models\User;
use App\Services\ProductionScreenshotService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\TaskFactory;
use Tests\TestCase;
use ZipArchive;

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

    public function test_archive_exactly_at_the_size_limit_is_attached(): void
    {
        $entries = $this->validEntry() + ["{$this->user->id}_" . Str::uuid() => str_repeat("\0", 70 * 1024)];
        config(['app.offline_screenshots.max_size' => array_sum(array_map('strlen', $entries))]);

        $response = $this->uploadOfflineArchive($this->user, $entries);

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->interval->refresh();
        $this->assertNull($this->interval->screenshot_id);
        $this->assertTmpIsEmpty();
    }

    /**
     * The zip headers claim a few bytes while the entry decompresses to several times the limit (a zip bomb with
     * forged sizes): the header check passes, the limit is enforced on the bytes actually extracted.
     */
    public function test_archive_with_forged_entry_sizes_is_rejected_while_extracting(): void
    {
        $maxSize = 1024 * 1024;
        config(['app.offline_screenshots.max_size' => $maxSize]);
        $extractedFileSizes = $this->recordExtractedFileSizesOnAttach();

        // 3 MiB of zeros, ~3 KiB packed; the CRC stays the one of the real content
        $name = "{$this->user->id}_$this->screenshotId";
        $zipPath = $this->createOfflineArchive([$name => str_repeat("\0", 3 * $maxSize)]);
        $this->forgeArchiveEntrySize($zipPath, $name, 10);

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $this->assertSame(10, $zip->statIndex(0)['size'], 'The forged header is not read back');
        $zip->close();

        $response = $this->uploadOfflineArchiveFile($this->user, $zipPath);

        // Without the limit on extraction the 3 MiB file is written and handed over to the screenshot service
        $this->assertSame([], $extractedFileSizes->all(), 'Sizes of the extracted files when attaching');
        $this->assertRejected($response, __('validation.offline-sync.too_large', ['max' => 1]));
    }

    /**
     * ZipArchive::extractTo() wrote such an entry as an empty file without reporting an error.
     */
    public function test_archive_with_a_corrupted_entry_is_rejected(): void
    {
        $name = "{$this->user->id}_$this->screenshotId";
        $zipPath = $this->createOfflineArchive([$name => $this->image()]);

        // An invalid deflate block type in place of the first compressed bytes of the only entry
        $bytes = file_get_contents($zipPath);
        $dataOffset = 30 + unpack('v', substr($bytes, 26, 2))[1] + unpack('v', substr($bytes, 28, 2))[1];
        file_put_contents($zipPath, substr_replace($bytes, str_repeat("\xFF", 8), $dataOffset, 8));

        $response = $this->uploadOfflineArchiveFile($this->user, $zipPath);

        $response->assertStatus(self::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(__('Cannot open file.'), $response->getContent());
        $this->interval->refresh();
        $this->assertSame($this->screenshotId, $this->interval->screenshot_id, 'The screenshot was attached');
        $this->assertTmpIsEmpty();
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
     * Overwrites the uncompressed size of an entry in both its local file header and its central directory record
     * (the archive has no data descriptors and no Zip64 records). The CRC-32 is left as is.
     */
    private function forgeArchiveEntrySize(string $zipPath, string $name, int $size): void
    {

        $bytes = file_get_contents($zipPath);
        $patched = false;
        for ($pos = strpos($bytes, "PK\x01\x02"); $pos !== false; $pos = strpos($bytes, "PK\x01\x02", $pos + 4)) {
            $nameLength = unpack('v', substr($bytes, $pos + 28, 2))[1];
            if (substr($bytes, $pos + 46, $nameLength) !== $name) {
                continue;
            }
            $localHeader = unpack('V', substr($bytes, $pos + 42, 4))[1];
            $bytes = substr_replace($bytes, pack('V', $size), $pos + 24, 4);
            $bytes = substr_replace($bytes, pack('V', $size), $localHeader + 22, 4);
            $patched = true;
        }
        $this->assertTrue($patched, "Entry $name not found in the archive");

        file_put_contents($zipPath, $bytes);
    }

    /**
     * Replaces the screenshot service with one that records the size of every file in storage/app/tmp whenever a
     * screenshot is attached, i.e. after the archive has been extracted.
     *
     * @return Collection<int, int>
     */
    private function recordExtractedFileSizesOnAttach(): Collection
    {
        $sizes = new Collection();
        $this->app->instance(ScreenshotService::class, new class ($sizes) extends ProductionScreenshotService {
            public function __construct(private readonly Collection $sizes)
            {
            }

            public function saveScreenshot($file, $timeInterval): void
            {
                $disk = Storage::disk('local');
                foreach ($disk->allFiles('tmp') as $path) {
                    $this->sizes->push($disk->size($path));
                }
                parent::saveScreenshot($file, $timeInterval);
            }
        });

        return $sizes;
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
