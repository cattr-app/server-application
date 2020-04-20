<?php

namespace App\Helpers;

use Nwidart\Modules\Facades\Module;

class ModuleHelper
{
    /**
     * Returns information about installed modules.
     */
    public static function getModulesInfo(): array
    {
        foreach (Module::all() as $name => $module) {
            $info[] = [
                'name' => $name,
                'version' => new Version($name),
                'enabled' => $module->isEnabled()
            ];
        }

        return $info ?? [];
    }
}
