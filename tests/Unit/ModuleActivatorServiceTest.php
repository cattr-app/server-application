<?php

namespace Tests\Unit;

use App\Services\ModuleActivatorService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mockery;
use Nwidart\Modules\Module;
use PHPUnit\Framework\TestCase;

class ModuleActivatorServiceTest extends TestCase
{
    private Container $previousContainer;

    private string|false $previousValue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        Container::setInstance(new Container());
        app()->instance('config', new Repository([
            'modules' => ['backend_activation' => []],
        ]));

        $this->previousValue = getenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED');
        putenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED');
    }

    protected function tearDown(): void
    {
        if ($this->previousValue === false) {
            putenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED');
        } else {
            putenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED=' . $this->previousValue);
        }

        Mockery::close();
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_module_is_disabled_when_cached_flag_is_absent(): void
    {
        $module = $this->redmineModule();
        $activator = new ModuleActivatorService();

        self::assertFalse($activator->hasStatus($module, true));
        self::assertTrue($activator->hasStatus($module, false));
    }

    public function test_module_activation_uses_cached_configuration_not_runtime_environment(): void
    {
        $module = $this->redmineModule();
        $activator = new ModuleActivatorService();

        putenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED=true');
        self::assertFalse($activator->hasStatus($module, true));

        config()->set('modules.backend_activation.CATTR_REDMINE_INTEGRATION', true);
        self::assertTrue($activator->hasStatus($module, true));
        self::assertFalse($activator->hasStatus($module, false));

        putenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED=false');
        self::assertTrue($activator->hasStatus($module, true));
    }

    public function test_activation_configuration_is_collected_from_environment(): void
    {
        $variables = [
            'MODULE_FRONTEND_AMAZING_CAT_REDMINE_INTEGRATION_ENABLED' => 'true',
            'MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED' => 'true',
            'MODULE_FRONTEND_AMAZING_CAT_GITLAB_INTEGRATION_ENABLED' => 'false',
            'MODULE_BACKEND_CATTR_GITLAB_INTEGRATION_ENABLED' => '1',
        ];

        $previous = [];

        try {
            foreach ($variables as $name => $value) {
                $previous[$name] = [
                    'process' => getenv($name),
                    'defined' => array_key_exists($name, $_ENV),
                    'env' => $_ENV[$name] ?? null,
                ];

                unset($_ENV[$name]);
                putenv("{$name}={$value}");
            }

            $activation = ModuleActivatorService::getActivation();

            self::assertTrue($activation['FRONTEND']['AMAZING_CAT_REDMINE_INTEGRATION']);
            self::assertTrue($activation['BACKEND']['CATTR_REDMINE_INTEGRATION']);
            self::assertFalse($activation['FRONTEND']['AMAZING_CAT_GITLAB_INTEGRATION']);
            self::assertFalse($activation['BACKEND']['CATTR_GITLAB_INTEGRATION']);
        } finally {
            foreach ($previous as $name => $value) {
                if ($value['process'] === false) {
                    putenv($name);
                } else {
                    putenv("{$name}={$value['process']}");
                }

                if ($value['defined']) {
                    $_ENV[$name] = $value['env'];
                } else {
                    unset($_ENV[$name]);
                }
            }
        }
    }

    public function test_module_status_cannot_be_changed_programmatically(): void
    {
        $this->expectException(\LogicException::class);

        (new ModuleActivatorService())->enable($this->redmineModule());
    }

    private function redmineModule(): Module
    {
        $module = Mockery::mock(Module::class);
        $module->shouldReceive('getName')->andReturn('CattrRedmineIntegration');

        return $module;
    }
}
