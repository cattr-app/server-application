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

    public function test_null_removes_the_setting(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $service->set('language', 'en');
        $service->set('language', null);

        $this->assertDatabaseMissing((new Setting)->getTable(), ['module_name' => 'test', 'key' => 'language']);
        $this->assertSame('default', $service->scope('test')->get('language', 'default'));
    }

    public function test_null_in_array_removes_only_that_setting(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $service->set(['language' => 'en', 'key' => 'value']);
        $service->scope('test')->set(['language' => null]);

        $this->assertNull($service->scope('test')->get('language'));
        $this->assertSame('value', $service->scope('test')->get('key'));
    }

    public function test_null_without_record_is_noop(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $service->set('missing', null);

        $this->assertNull($service->scope('test')->get('missing'));
    }

    public function test_null_does_not_remove_when_only_if_not_exists(): void
    {
        $service = resolve(SettingsProviderService::class)->scope('test');
        $service->set('language', 'en');
        $service->scope('test')->set('language', null, true);

        $this->assertSame('en', $service->scope('test')->get('language'));
    }
}
