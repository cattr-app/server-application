<?php

namespace Tests\Feature\About;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Nwidart\Modules\Collection as ModuleCollection;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Laravel\Module as ModuleInstance;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    private const URI = 'about';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('app.modules');

        $module = Mockery::mock(ModuleInstance::class)->shouldIgnoreMissing();
        $module->shouldReceive('isStatus')->andReturn(false);
        $module->shouldReceive('isEnabled')->andReturn(false);
        $module->shouldReceive('getPath')->andReturn('/nonexistent/module');

        $repository = Module::partialMock();
        $repository->shouldReceive('all')->andReturn(['CattrDemo' => $module]);
        $repository->shouldReceive('toCollection')->andReturn(new ModuleCollection(['CattrDemo' => $module]));
    }

    protected function tearDown(): void
    {
        Cache::forget('app.modules');

        parent::tearDown();
    }

    private function modules(): array
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        return $this->actingAs($admin)->getJson(self::URI)->assertOk()->json('data.modules');
    }

    public function test_installed_modules_are_kept_when_the_collector_returns_an_empty_list(): void
    {
        Http::fake(['*/modules' => Http::response(['statusCode' => 200, 'modules' => []])]);

        $this->assertSame(
            [['name' => 'CattrDemo', 'version' => 'dev', 'enabled' => false]],
            $this->modules()
        );
    }

    public function test_installed_modules_are_kept_when_the_collector_response_has_no_modules(): void
    {
        Http::fake(['*/modules' => Http::response(['statusCode' => 200])]);

        $this->assertCount(1, $this->modules());
    }

    public function test_installed_modules_are_kept_when_the_collector_fails(): void
    {
        Http::fake(['*/modules' => Http::response('error', 500)]);

        $this->assertCount(1, $this->modules());
    }

    public function test_collector_data_enriches_known_modules(): void
    {
        Http::fake(['*/modules' => Http::response(['modules' => [
            ['name' => 'cattrdemo', 'version' => '9.9.9', 'enabled' => false, 'vulnerable' => true, 'last_version' => '4.0.0'],
        ]])]);

        $this->assertSame(
            [['name' => 'CattrDemo', 'version' => 'dev', 'enabled' => false, 'vulnerable' => true, 'last_version' => '4.0.0']],
            $this->modules()
        );
    }
}
