<?php


namespace App\Services;

use Cache;
use Exception;
use JsonException;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;
use App\Models\Module as ModuleModel;

class ModuleActivatorService implements ActivatorInterface
{

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function enable(Module $module): void
    {
        $this->setActiveByName($module->getName(), true);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function hasStatus(Module $module, bool $status): bool
    {
        $moduleStatuses = Cache::store('octane')
            ->rememberForever(config('modules.activators.amazing.cache_key'), function () {
                $configFile = config('modules.activators.amazing.file_name');

                $databaseModules = [];

                try {
                    foreach (ModuleModel::all()->toArray() as $module) {
                        $databaseModules[$module['name']] = $module['enabled'];
                    }
                } catch (Exception) {
                    // We can't communicate with db - then do nothing
                    // This can happen on first install when we are trying to migrate over clear database
                }

                return array_merge(
                    $this->getFileConfig($configFile),
                    $this->getFileConfig("$configFile." . config('app.env')),
                    $this->getFileConfig("$configFile.local"),
                    $databaseModules,
                );
            });

        if (isset($moduleStatuses[$module->getName()])) {
            return $moduleStatuses[$module->getName()] ?? false === $status;
        }

        return (bool)$module->json()?->active;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setActiveByName(string $name, bool $active): void
    {
        ModuleModel::firstOrCreate(['name' => $name])->update(['enabled' => $active]);
        $this->flushCache();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function delete(Module $module): void
    {
        ModuleModel::firstOrFail($module->getName())->delete();
        $this->flushCache();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function reset(): void
    {
        ModuleModel::truncate();
        $this->flushCache();
    }

    /**
     * @param $name
     * @return array
     * @throws JsonException
     */
    private function getFileConfig($name): array
    {
        $filePath = base_path("$name.json");
        return file_exists($filePath) ? json_decode(file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * @throws Exception
     */
    private function flushCache(): void
    {
        Cache::store('octane')->forget(config('modules.activators.amazing.cache_key'));
    }
}
