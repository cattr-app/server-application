<?php

namespace App\Helpers;

use Composer\InstalledVersions;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use Exception;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use RuntimeException;
use Throwable;

//use Module;

class Version
{
    private string $version;

    /**
     * @throws Throwable
     */
    public function __construct(protected ?string $module = null)
    {
        throw_if($module && !isset(self::getModules()[$module]), new RuntimeException('No such module'));

        if ($module) {
            $this->version = self::getModules()[$module];
            return;
        }

        $this->version = env('APP_VERSION', 'dev') ?: self::getComposerVersion(base_path());
    }

    public static function getModules(): array
    {
        return cache()->rememberForever('app.modules', static function() {
            try {
                return Module::toCollection()->map(static function (\Nwidart\Modules\Laravel\Module $module) {
                    $modulePath = $module->getPath();

                    try {
                        return self::getComposerVersion($modulePath);
                    } catch (Throwable) {
                        try {
                            return self::getFileVersion($modulePath);
                        } catch (Throwable) {
                            try {
                                return self::getGitVersion($modulePath);
                            } catch (Throwable) {
                                return 'dev';
                            }
                        }
                    }
                })->toArray();
            } catch (Throwable) {
                return [];
            }
        });
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

    /**
     * @throws GitException
     */
    private static function getGitVersion(string $path): string
    {
        $repo = (new Git())->open($path);

        $tags = $repo->getTags();

        return array_pop($tags);
    }

    public function __toString(): string
    {
        return preg_replace('/^v(.*)$/', '$1', $this->version);
    }
}
