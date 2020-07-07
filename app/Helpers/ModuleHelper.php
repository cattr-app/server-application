<?php

namespace App\Helpers;

use JsonException;
use Module;

class ModuleHelper
{
    /**
     * Returns information about installed modules.
     * @throws JsonException
     */
    public static function getModulesInfo(): array
    {
        foreach (Module::all() as $name => $module) {
            $info[] = [
                'name' => $name,
                'version' => (string)(new Version($name)),
                'enabled' => $module->isEnabled()
            ];
        }

        return $info ?? [];
    }
}
