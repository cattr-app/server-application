<?php

namespace Tests\Feature\Storage;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanTmpFilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_deletes_only_old_entries_of_the_tmp_directory(): void
    {
        $disk = Storage::disk('local');

        $disk->put('tmp/old-upload.png', 'old');
        $disk->put('tmp/new-upload.png', 'new');
        $disk->put('tmp/1234-5678/7_old-screenshot', 'old');
        $disk->put('tmp/9876-5432/7_new-screenshot', 'new');
        $disk->put('screenshots/old.jpg', 'outside tmp');
        $disk->put('outside/kept.txt', 'linked from tmp');
        symlink($disk->path('outside'), $disk->path('tmp/1234-5678/link-to-outside'));

        $this->age('tmp/old-upload.png', 25);
        $this->age('tmp/1234-5678', 25);
        $this->age('screenshots/old.jpg', 25);

        $this->artisan('cattr:tmp:clean')
            ->expectsOutputToContain('Deleted 2 entries older than 24 h')
            ->assertSuccessful();

        $this->assertFalse($disk->exists('tmp/old-upload.png'));
        $this->assertFalse(is_dir($disk->path('tmp/1234-5678')));
        $this->assertTrue($disk->exists('tmp/new-upload.png'));
        $this->assertTrue($disk->exists('tmp/9876-5432/7_new-screenshot'));
        $this->assertTrue($disk->exists('screenshots/old.jpg'));
        $this->assertTrue($disk->exists('outside/kept.txt'), 'A symbolic link inside tmp must not be followed');
    }

    public function test_hours_option_sets_the_age_threshold(): void
    {
        $disk = Storage::disk('local');
        $disk->put('tmp/upload.png', 'x');
        $this->age('tmp/upload.png', 25);

        $this->artisan('cattr:tmp:clean', ['--hours' => 48])->assertSuccessful();
        $this->assertTrue($disk->exists('tmp/upload.png'));

        $this->artisan('cattr:tmp:clean', ['--hours' => 2])->assertSuccessful();
        $this->assertFalse($disk->exists('tmp/upload.png'));
    }

    public function test_rejects_an_invalid_hours_option(): void
    {
        $disk = Storage::disk('local');
        $disk->put('tmp/upload.png', 'x');
        $this->age('tmp/upload.png', 25);

        $this->artisan('cattr:tmp:clean', ['--hours' => 0])->assertFailed();
        $this->artisan('cattr:tmp:clean', ['--hours' => 'abc'])->assertFailed();

        $this->assertTrue($disk->exists('tmp/upload.png'));
    }

    public function test_succeeds_without_a_tmp_directory(): void
    {
        $this->artisan('cattr:tmp:clean')->expectsOutputToContain('Nothing to clean')->assertSuccessful();
    }

    private function age(string $path, int $hours): void
    {
        $time = now()->subHours($hours)->getTimestamp();
        touch(Storage::disk('local')->path($path), $time, $time);
    }
}
