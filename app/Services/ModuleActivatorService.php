<?php

namespace App\Services;

use Illuminate\Support\Str;
use LogicException;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

class ModuleActivatorService implements ActivatorInterface
{
    public static function getActivation(): array
    {
        $moduleActivation = ['FRONTEND' => [], 'BACKEND' => []];

        foreach (array_merge($_ENV, getenv() ?: []) as $key => $value) {
            if (preg_match('/^MODULE_(FRONTEND|BACKEND)_([A-Z][A-Z0-9_]*)_ENABLED$/D', $key, $matches)) {
                $moduleActivation[$matches[1]][$matches[2]] = $value === 'true';
            }
        }

        return $moduleActivation;
    }

    public function enable(Module $module): void
    {
        $this->denyMutation();
    }

    public function disable(Module $module): void
    {
        $this->denyMutation();
    }

    public function hasStatus(Module|string $module, bool $status): bool
    {
        // nwidart/laravel-modules >= 12 passes the module name while building the manifest.
        $name = Str::upper(Str::snake($module instanceof Module ? $module->getName() : $module));
        return config("modules.backend_activation.{$name}", false) === $status;
    }

    public function setActive(Module $module, bool $active): void
    {
        $this->denyMutation();
    }

    public function setActiveByName(string $name, bool $active): void
    {
        $this->denyMutation();
    }

    public function delete(Module $module): void
    {
        $this->denyMutation();
    }

    public function reset(): void
    {
        $this->denyMutation();
    }

    private function denyMutation(): void
    {
        throw new LogicException('Module activation is read-only; configure MODULE_BACKEND_<NAME>_ENABLED and restart the container.');
    }
}
