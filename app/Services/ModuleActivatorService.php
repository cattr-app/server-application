<?php

namespace App\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use LogicException;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;
use Throwable;

/**
 * Read-only module activator: a backend module is enabled only when
 * MODULE_BACKEND_<FLAG_NAME>_ENABLED=true was present when the configuration was built.
 *
 * @see docs/dev/module-activation-flags.md
 */
class ModuleActivatorService implements ActivatorInterface
{
    private const ENV_PREFIX = 'MODULE_';

    private static ?array $activation = null;

    private Repository $config;

    public function __construct(private readonly Container $app)
    {
        $this->config = $app->make('config');
    }

    /**
     * Derive <FLAG_NAME> of MODULE_<SIDE>_<FLAG_NAME>_ENABLED from a module identifier.
     * Must stay in sync with resources/frontend/core/utils/moduleFlagName.js.
     */
    public static function flagName(string $identifier): string
    {
        // 1. Split words: a lowercase letter or digit followed by an uppercase letter.
        $name = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $identifier);
        // 2. Replace every run of characters other than ASCII letters and digits with one underscore.
        $name = preg_replace('/[^A-Za-z0-9]+/', '_', $name);
        // 3. Uppercase.
        $name = strtoupper($name);

        // 4. Collapse repeated underscores and trim them from both ends.
        return trim(preg_replace('/_+/', '_', $name), '_');
    }

    /**
     * @return array{FRONTEND: array<string, bool>, BACKEND: array<string, bool>}
     */
    public static function getActivation(): array
    {
        if (self::$activation !== null) {
            return self::$activation;
        }

        $moduleActivation = ['FRONTEND' => [], 'BACKEND' => []];

        foreach (array_merge($_ENV, getenv() ?: []) as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, self::ENV_PREFIX)) {
                continue;
            }

            if (preg_match('/^MODULE_(FRONTEND|BACKEND)_([A-Z][A-Z0-9_]*)_ENABLED$/D', $key, $matches)) {
                $moduleActivation[$matches[1]][$matches[2]] = $value === 'true';
            }
        }

        return self::$activation = $moduleActivation;
    }

    public static function resetActivationCache(): void
    {
        self::$activation = null;
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
        $flag = self::flagName($module instanceof Module ? $module->getName() : $module);

        return $this->config->get("modules.backend_activation.{$flag}", false) === $status;
    }

    public function setActive(Module $module, bool $active): void
    {
        $this->denyMutation();
    }

    public function setActiveByName(string $name, bool $active): void
    {
        // module:make calls this after generating files; activation stays env-controlled.
        $this->warnNothingChanged('setActiveByName');
    }

    public function delete(Module $module): void
    {
        $this->warnNothingChanged('delete');
    }

    public function reset(): void
    {
        $this->warnNothingChanged('reset');
    }

    private function warnNothingChanged(string $operation): void
    {
        try {
            if ($this->app->bound('log')) {
                $this->app->make('log')->warning(
                    "Module activation is environment-controlled; {$operation} changed nothing."
                );
            }
        } catch (Throwable) {
            // Logging is best effort: these operations are documented no-ops and must not fail.
        }
    }

    private function denyMutation(): void
    {
        throw new LogicException(
            'Module activation is read-only; configure MODULE_BACKEND_<NAME>_ENABLED and restart the container.'
        );
    }
}
