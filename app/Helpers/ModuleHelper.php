<?php

namespace App\Helpers;

use JsonException;
use Module;
use Throwable;

class ModuleHelper
{
    /**
     * Returns information about installed modules.
     * @throws JsonException
     */
    public static function getModulesInfo(): array
    {
        foreach (Module::all() as $name => $module) {
            try {
                $version = (string)(new Version($name));
            } catch (Throwable) {
                $version = 'dev';
            }

            $info[] = [
                'name' => $name,
                'version' => $version,
                'enabled' => $module->isEnabled()
            ];
        }

        return $info ?? [];
    }
}
