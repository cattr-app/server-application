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

    private bool $modulesInstalled = false;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('app.modules');
    }

    protected function tearDown(): void
    {
        Cache::forget('app.modules');

        parent::tearDown();
    }

    /**
     * Like nwidart: the registry is keyed by the lowercased name, the module keeps its own name.
     *
     * @param string[] $names
     * @param string[] $disabled names of the modules that are not enabled
     */
    private function installModules(array $names, array $disabled = []): void
    {
        $this->modulesInstalled = true;

        $installed = [];
        foreach ($names as $name) {
            $enabled = !in_array($name, $disabled, true);
            $module = Mockery::mock(ModuleInstance::class)->shouldIgnoreMissing();
            $module->shouldReceive('getName')->andReturn($name);
            $module->shouldReceive('isStatus')->andReturn($enabled);
            $module->shouldReceive('isEnabled')->andReturn($enabled);
            $module->shouldReceive('getPath')->andReturn('/nonexistent/module');
            $installed[strtolower($name)] = $module;
        }

        $repository = Module::partialMock();
        $repository->shouldReceive('all')->andReturn($installed);
        // RegisterModulesEvents boots the providers of enabled modules: not what is under test here.
        $repository->shouldReceive('allEnabled')->andReturn([]);
        $repository->shouldReceive('toCollection')->andReturn(new ModuleCollection($installed));
    }

    private function modules(): array
    {
        if (!$this->modulesInstalled) {
            $this->installModules(['CattrDemo']);
        }

        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        return $this->actingAs($admin)->getJson(self::URI)->assertOk()->json('data.modules');
    }

    public function test_installed_modules_are_kept_when_the_collector_returns_an_empty_list(): void
    {
        Http::fake(['*/modules' => Http::response(['statusCode' => 200, 'modules' => []])]);

        $this->assertSame(
            [['name' => 'CattrDemo', 'version' => 'dev', 'enabled' => true]],
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
            [
                'name' => 'cattrdemo',
                'version' => '9.9.9',
                'enabled' => true,
                'vulnerable' => true,
                'last_version' => '4.0.0',
            ],
        ]])]);

        $this->assertSame(
            [[
                'name' => 'CattrDemo',
                'version' => 'dev',
                'enabled' => true,
                'vulnerable' => true,
                'last_version' => '4.0.0',
            ]],
            $this->modules()
        );
    }

    public function test_modules_missing_from_a_partial_collector_response_are_kept_unchanged(): void
    {
        $this->installModules(['CattrDemo', 'CattrOther']);
        Http::fake(['*/modules' => Http::response(['modules' => [
            [
                'name' => 'cattrdemo',
                'version' => '9.9.9',
                'enabled' => true,
                'vulnerable' => true,
                'last_version' => '4.0.0',
            ],
        ]])]);

        $this->assertSame(
            [
                [
                    'name' => 'CattrDemo',
                    'version' => 'dev',
                    'enabled' => true,
                    'vulnerable' => true,
                    'last_version' => '4.0.0',
                ],
                ['name' => 'CattrOther', 'version' => 'dev', 'enabled' => true],
            ],
            $this->modules()
        );
    }

    public function test_installed_modules_are_kept_when_the_cached_module_list_predates_them(): void
    {
        Cache::forever('app.modules', ['CattrOld' => '1.0.0']);
        Http::fake(['*/modules' => Http::response('error', 500)]);

        $this->assertSame(
            [['name' => 'CattrDemo', 'version' => 'dev', 'enabled' => true]],
            $this->modules()
        );
        $this->assertSame(['cattrdemo' => 'dev'], Cache::get('app.modules'));
    }

    public function test_an_empty_cached_module_list_is_rebuilt(): void
    {
        Cache::forever('app.modules', []);
        Http::fake(['*/modules' => Http::response('error', 500)]);

        $this->assertCount(1, $this->modules());
        $this->assertSame(['cattrdemo' => 'dev'], Cache::get('app.modules'));
    }

    public function test_only_enabled_modules_are_listed_while_the_collector_receives_all_of_them(): void
    {
        $this->installModules(['Cattr/Gitlab_Integration', 'Cattr-Off_Module'], ['Cattr-Off_Module']);
        Http::fake(['*/modules' => Http::response(['modules' => []])]);

        $this->assertSame(
            [['name' => 'Cattr/Gitlab_Integration', 'version' => 'dev', 'enabled' => true]],
            $this->modules()
        );

        Http::assertSent(static fn ($request) => str_ends_with($request->url(), '/modules')
            && $request['0']['name'] === 'Cattr/Gitlab_Integration' && $request['0']['enabled'] === true
            && $request['1']['name'] === 'Cattr-Off_Module' && $request['1']['enabled'] === false);
    }

    public function test_the_module_list_is_empty_when_every_module_is_disabled(): void
    {
        $this->installModules(['CattrDemo'], ['CattrDemo']);
        Http::fake(['*/modules' => Http::response('error', 500)]);

        $this->assertSame([], $this->modules());
    }
}
