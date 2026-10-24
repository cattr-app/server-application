<?php

namespace Tests\Unit;

use App\Services\ModuleActivatorService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use LogicException;
use Nwidart\Modules\Module;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleActivatorServiceTest extends TestCase
{
    private const REDMINE_FLAG = 'MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED';

    private Container $container;

    private Repository $config;

    private string|false $previousValue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Repository(['modules' => ['backend_activation' => []]]);
        $this->container = new Container();
        $this->container->instance('config', $this->config);

        $this->previousValue = getenv(self::REDMINE_FLAG);
        putenv(self::REDMINE_FLAG);
        ModuleActivatorService::resetActivationCache();
    }

    protected function tearDown(): void
    {
        if ($this->previousValue === false) {
            putenv(self::REDMINE_FLAG);
        } else {
            putenv(self::REDMINE_FLAG . '=' . $this->previousValue);
        }

        ModuleActivatorService::resetActivationCache();
        parent::tearDown();
    }

    public static function flagNameFixtures(): array
    {
        $fixtures = json_decode(
            file_get_contents(dirname(__DIR__) . '/fixtures/module-flag-names.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $cases = [];
        foreach ($fixtures as $identifier => $flag) {
            $cases[$identifier] = [$identifier, $flag];
        }

        return $cases;
    }

    #[DataProvider('flagNameFixtures')]
    public function test_flag_name_matches_shared_fixtures(string $identifier, string $flag): void
    {
        self::assertSame($flag, ModuleActivatorService::flagName($identifier));
    }

    public function test_module_is_disabled_when_cached_flag_is_absent(): void
    {
        $module = $this->redmineModule();
        $activator = $this->activator();

        self::assertFalse($activator->hasStatus($module, true));
        self::assertTrue($activator->hasStatus($module, false));
    }

    public function test_module_activation_uses_cached_configuration_not_runtime_environment(): void
    {
        $module = $this->redmineModule();
        $activator = $this->activator();

        putenv(self::REDMINE_FLAG . '=true');
        self::assertFalse($activator->hasStatus($module, true));

        $this->config->set('modules.backend_activation.CATTR_REDMINE_INTEGRATION', true);
        self::assertTrue($activator->hasStatus($module, true));
        self::assertFalse($activator->hasStatus($module, false));

        putenv(self::REDMINE_FLAG . '=false');
        self::assertTrue($activator->hasStatus($module, true));
    }

    public function test_has_status_accepts_plain_module_name(): void
    {
        $activator = $this->activator();

        self::assertFalse($activator->hasStatus('CattrRedmineIntegration', true));

        $this->config->set('modules.backend_activation.CATTR_REDMINE_INTEGRATION', true);
        self::assertTrue($activator->hasStatus('CattrRedmineIntegration', true));
        self::assertFalse($activator->hasStatus('CattrRedmineIntegration', false));
    }

    public function test_activation_configuration_is_collected_from_environment(): void
    {
        $variables = [
            'MODULE_FRONTEND_AMAZING_CAT_REDMINE_INTEGRATION_ENABLED' => 'true',
            'MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED' => 'true',
            'MODULE_FRONTEND_AMAZING_CAT_GITLAB_INTEGRATION_ENABLED' => 'false',
            'MODULE_BACKEND_CATTR_GITLAB_INTEGRATION_ENABLED' => '1',
            'MODULE_BACKEND_lowercase_ENABLED' => 'true',
            'XMODULE_BACKEND_PREFIXED_ENABLED' => 'true',
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
            self::assertArrayNotHasKey('lowercase', $activation['BACKEND']);
            self::assertArrayNotHasKey('PREFIXED', $activation['BACKEND']);
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

    public function test_activation_is_memoized_until_cache_is_reset(): void
    {
        $name = 'MODULE_BACKEND_MEMOIZED_PROBE_ENABLED';
        $previous = getenv($name);

        try {
            putenv("{$name}=true");
            self::assertTrue(ModuleActivatorService::getActivation()['BACKEND']['MEMOIZED_PROBE']);

            putenv("{$name}=false");
            self::assertTrue(ModuleActivatorService::getActivation()['BACKEND']['MEMOIZED_PROBE']);

            ModuleActivatorService::resetActivationCache();
            self::assertFalse(ModuleActivatorService::getActivation()['BACKEND']['MEMOIZED_PROBE']);
        } finally {
            putenv($previous === false ? $name : "{$name}={$previous}");
        }
    }

    public function test_module_status_cannot_be_changed_programmatically(): void
    {
        $activator = $this->activator();
        $module = $this->redmineModule();

        $mutations = [
            'enable' => static fn () => $activator->enable($module),
            'disable' => static fn () => $activator->disable($module),
            'setActive' => static fn () => $activator->setActive($module, true),
            'setActiveByName' => static fn () => $activator->setActiveByName('CattrRedmineIntegration', true),
        ];

        foreach ($mutations as $method => $mutation) {
            try {
                $mutation();
                self::fail("{$method}() must throw LogicException.");
            } catch (LogicException $exception) {
                self::assertStringContainsString('read-only', $exception->getMessage());
            }
        }

        self::assertFalse($activator->hasStatus($module, true));
    }

    public function test_delete_and_reset_are_logged_no_ops(): void
    {
        $logger = new class {
            public array $warnings = [];

            public function warning(string $message): void
            {
                $this->warnings[] = $message;
            }
        };
        $this->container->instance('log', $logger);
        $this->config->set('modules.backend_activation.CATTR_REDMINE_INTEGRATION', true);

        $activator = $this->activator();
        $activator->delete($this->redmineModule());
        $activator->reset();

        self::assertSame([
            'Module activation is environment-controlled; delete changed nothing.',
            'Module activation is environment-controlled; reset changed nothing.',
        ], $logger->warnings);
        self::assertTrue($activator->hasStatus('CattrRedmineIntegration', true));
    }

    public function test_delete_and_reset_do_not_fail_without_a_working_logger(): void
    {
        $activator = $this->activator();
        $activator->delete($this->redmineModule());
        $activator->reset();

        $this->container->bind('log', static fn () => throw new RuntimeException('logger unavailable'));
        $activator->delete($this->redmineModule());
        $activator->reset();

        self::assertFalse($activator->hasStatus('CattrRedmineIntegration', true));
    }

    private function activator(): ModuleActivatorService
    {
        return new ModuleActivatorService($this->container);
    }

    private function redmineModule(): Module
    {
        return new class ('CattrRedmineIntegration') extends Module {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(string $name)
            {
                $this->name = $name;
                $this->path = sys_get_temp_dir();
            }

            public function registerAliases(): void
            {
            }

            public function registerProviders(): void
            {
            }

            public function getCachedServicesPath(): string
            {
                return '';
            }
        };
    }
}
