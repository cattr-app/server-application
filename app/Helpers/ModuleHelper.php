<?php

namespace App\Helpers;

use JsonException;
use Module;
use Throwable;

class ModuleHelper
{
    /**
     * Returns information about installed modules, enabled or not.
     * `name` is the module's own name from module.json: the registry keys are lowercased by nwidart.
     * @throws JsonException
     */
    public static function getModulesInfo(): array
    {
        foreach (Module::all() as $key => $module) {
            try {
                // Version looks modules up by the registry key, not by the name.
                $version = (string)(new Version($key));
            } catch (Throwable) {
                $version = 'dev';
            }

            $info[] = [
                'name' => $module->getName(),
                'version' => $version,
                'enabled' => $module->isEnabled()
            ];
        }

        return $info ?? [];
    }
}
