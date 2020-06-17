<?php

namespace App\Services;

use App\Contracts\Settings as SettingsInterface;
use App\Models\Setting;

class SettingsService implements SettingsInterface
{
    /**
     * @var Setting
     */
    protected Setting $model;

    /**
     * SettingsService constructor.
     * @param Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->model = $setting;
    }

    /**
     * Get all settings.
     *
     * @param string $moduleName
     * @return mixed
     */
    public function all(string $moduleName): array
    {
        return $this->prepareCollection($this->model->where(['module_name' => $moduleName])->get());
    }

    /**
     * Get the settings value by key.
     *
     * @param string $moduleName
     * @param string $key
     * @param null $default
     * @return |null
     */
    public function get(string $moduleName, string $key, $default = null)
    {
        $setting = $this->model->where(['module_name' => $moduleName, 'key' => $key])->get()->first();

        return optional($setting)->value ?? $default;
    }

    /**
     * Set the setting value.
     *
     * An key value array can be passed as key.
     *
     * @param string $moduleName
     * @param $key
     * @param null $value
     * @return array
     */
    public function set(string $moduleName, $key, $value = null): array
    {
        if (is_array($key)) {
            $settings = [];

            foreach ($key as $_key => $_value) {
                $setting = $this->model->updateOrCreate([
                    'module_name' => $moduleName,
                    'key' => $_key,
                ], [
                    'value' => $_value
                ]);

                $settings = array_merge($settings, $this->prepare($setting));
            }

            return $settings;
        }

        $setting = $this->model->updateOrCreate([
            'module_name' => $moduleName,
            'key' => $key,
        ], [
            'value' => $value
        ]);

        return $this->prepare($setting);
    }

    /**
     * Prepares model to the key value view.
     *
     * @param $setting
     * @return array
     */
    protected function prepare($setting): array
    {
        return [$setting->key => $setting->value];
    }

    /**
     * Prepares collection to the key value view.
     *
     * @param $collection
     * @return mixed
     */
    protected function prepareCollection($collection): array
    {
        return $collection
            ->map(function ($item) {
                return $this->prepare($item);
            })
            ->collapse()
            ->toArray();
    }
}
