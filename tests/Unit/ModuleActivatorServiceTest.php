<?php

namespace Tests\Unit;

use App\Services\ModuleActivatorService;
use Mockery;
use Nwidart\Modules\Module;
use PHPUnit\Framework\TestCase;

class ModuleActivatorServiceTest extends TestCase
{
    private string|false $previousValue;

    protected function setUp(): void
    {
        parent::setUp();
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
        parent::tearDown();
    }

    public function test_module_is_disabled_when_flag_is_absent(): void
    {
        $module = $this->redmineModule();
        $activator = new ModuleActivatorService();

        self::assertFalse($activator->hasStatus($module, true));
        self::assertTrue($activator->hasStatus($module, false));
    }

    public function test_only_explicit_lowercase_true_enables_module(): void
    {
        $module = $this->redmineModule();
        $activator = new ModuleActivatorService();

        foreach (['false', '1', 'TRUE', 'yes'] as $value) {
            putenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED=' . $value);
            self::assertFalse($activator->hasStatus($module, true), $value);
        }

        putenv('MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED=true');
        self::assertTrue($activator->hasStatus($module, true));
        self::assertFalse($activator->hasStatus($module, false));
    }

    public function test_module_status_cannot_be_changed_in_database(): void
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
