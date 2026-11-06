<?php

namespace App\Helpers;

use Composer\InstalledVersions;
use Exception;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use RuntimeException;
use Throwable;

//use Module;

class Version
{
    private const MODULES_CACHE_KEY = 'app.modules';

    private string $version;

    /**
     * @throws Throwable
     */
    public function __construct(protected ?string $module = null)
    {
        if ($module) {
            $modules = self::getModules();

            // The cached list may predate the module (installed or enabled after it was built): rebuild it once.
            if (!isset($modules[$module])) {
                cache()->forget(self::MODULES_CACHE_KEY);
                $modules = self::getModules();
            }

            throw_unless(isset($modules[$module]), new RuntimeException('No such module'));

            $this->version = $modules[$module];
            return;
        }

        $this->version = env('APP_VERSION', 'dev') ?: self::getComposerVersion(base_path());
    }

    public static function getModules(): array
    {
        $cached = cache()->get(self::MODULES_CACHE_KEY);

        // An empty list is never trusted: it is what a failed scan used to leave in the cache forever.
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $modules = Module::toCollection()->map(static function (\Nwidart\Modules\Laravel\Module $module) {
                $modulePath = $module->getPath();

                try {
                    return self::getComposerVersion($modulePath);
                } catch (Throwable) {
                    try {
                        return self::getFileVersion($modulePath);
                    } catch (Throwable) {
                        return 'dev';
                    }
                }
            })->toArray();
        } catch (Throwable) {
            return [];
        }

        if ($modules !== []) {
            cache()->forever(self::MODULES_CACHE_KEY, $modules);
        }

        return $modules;
    }

    /**
     * @throws Throwable
     */
    private static function getComposerVersion(string $path): string
    {
        throw_unless(file_exists("$path/composer.json"));

        $composerConfig = file_get_contents("$path/composer.json");

        throw_unless(Str::isJson($composerConfig));

        $package = json_decode($composerConfig, true, 512, JSON_THROW_ON_ERROR);

        throw_unless(InstalledVersions::isInstalled($package['name']) || isset($package['version']));

        return $package['version'] ?? InstalledVersions::getVersion($package['name']);
    }

    /**
     * @throws Throwable
     */
    private static function getFileVersion(string $path): string
    {
        throw_unless(file_exists("$path/module.json"));

        $moduleConfig = file_get_contents("$path/module.json");

        throw_unless(Str::isJson($moduleConfig));

        $module = json_decode($moduleConfig, true, 512, JSON_THROW_ON_ERROR);

        throw_unless(isset($module['version']));

        return $module['version'];
    }

    public function __toString(): string
    {
        return preg_replace('/^v(.*)$/', '$1', $this->version);
    }
}
