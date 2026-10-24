<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingsProviderService;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_get_setting(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $service->set('language', 'en');

        $this->assertSame('en', $service->get('language'));
    }

    public function test_get_all_settings(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $service->set(['language' => 'en', 'key' => 'value']);

        $settings = $service->all();
        $this->assertCount(2, $settings);
        $this->assertSame('en', $settings['language']);
        $this->assertSame('value', $settings['key']);
    }

    public function test_set_one_setting(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $service->set('language', 'en');

        $this->assertDatabaseHas((new Setting)->getTable(), [
            'module_name' => 'test',
            'key' => 'language',
            'value' => 'en',
        ]);
    }

    public function test_set_multiple_settings(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $data = ['language' => 'en', 'timezone' => 'utc'];

        $service->set($data);

        $settings = $service->all();
        ksort($data);
        ksort($settings);

        $this->assertSame($data, $settings);
    }
}
