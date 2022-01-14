<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingsProviderService;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_get_setting(): void
    {
        $service = resolve(SettingsProviderService::class);

        $service->set('test', 'language', 'en');

        $setting = $service->get('test', 'language');

        $this->assertEquals($setting, 'en');
    }

    public function test_get_all_settings(): void
    {
        $service = resolve(SettingsProviderService::class);

        $service->set('test', 'language', 'en');
        $service->set('test', 'key', 'value');

        $settings = $service->all('test');

        $this->assertDatabaseHas((new Setting)->getTable(), ['value' => reset($settings)]);
    }

    public function test_set_one_setting(): void
    {
        $service = resolve(SettingsProviderService::class);

        $result = $service->set('test', 'language', 'en');

        $this->assertEquals($result, ['language' => 'en']);
    }

    public function test_set_multiple_settings(): void
    {
        $service = resolve(SettingsProviderService::class);

        $data = ['language' => 'en', 'timezone' => 'utc'];

        $result = $service->set('test', $data);

        $this->assertEquals($result, $data);
    }
}
