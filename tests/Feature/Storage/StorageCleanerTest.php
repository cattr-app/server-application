<?php

namespace Tests\Feature\Storage;

use App\Helpers\StorageCleaner;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class StorageCleanerTest extends TestCase
{
    public function test_rotation_is_supported_on_local_disk(): void
    {
        Storage::fake('local');

        $this->assertTrue(StorageCleaner::isSupported());
        $this->assertGreaterThan(0, StorageCleaner::getFreeSpace());
    }

    public function test_rotation_is_a_noop_on_s3(): void
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');

        $this->assertFalse(StorageCleaner::isSupported());
        $this->assertEquals(0, StorageCleaner::getFreeSpace());
        $this->assertEquals(0, StorageCleaner::getUsedSpace());
        $this->assertFalse(StorageCleaner::needThinning());
        $this->assertSame(0, StorageCleaner::countAvailableScreenshots());
        StorageCleaner::thin(true);

        $this->artisan('cattr:screenshots:rotate')
            ->expectsOutputToContain('bucket lifecycle rules')
            ->assertSuccessful();

        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->actingAs($admin)->getJson('about/storage')
            ->assertOk()
            ->assertJsonPath('data.rotation_supported', false)
            ->assertJsonPath('data.need_thinning', false);
    }
}
