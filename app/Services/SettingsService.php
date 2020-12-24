<?php

namespace App\Services;

use App\Contracts\Settings as SettingsInterface;
use App\Models\Setting;
use Exception;
use RuntimeException;
use Schema;

class SettingsService implements SettingsInterface
{
    /**
     * @var Setting
     */
    protected Setting $model;

    protected string $scope = '';

    /**
     * SettingsService constructor.
     * @param Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->model = $setting;
    }

    public function scope(string $moduleName): SettingsService
    {
        $this->scope = $moduleName;

        return $this;
    }

    /**
     * Get all settings.
     *
     * @param string $moduleName
     * @return mixed
     * @throws Exception
     */
    public function all(string $moduleName = null): array
    {
        if ($moduleName === null && $this->scope === '') {
            throw new RuntimeException('Scope for settings is missing');
        }

        $scope = $moduleName ?? $this->scope;

        $this->scope = '';

        $result = $this->prepareCollection($this->model->where(['module_name' => $scope])->get());

        cache()->forever("settings:$scope", $result);

        return $result;
    }

    /**
     * Get the settings value by key.
     *
     * @param string $moduleName
     * @param mixed $key
     * @param mixed $default
     * @return mixed|null
     * @throws Exception
     */
    public function get(string $moduleName, $key = null, $default = null)
    {
        if ($this->scope !== '') {
            $scope = $this->scope;
            $_key = $moduleName;
            $_default = $key;
        } else {
            $scope = $moduleName;
            $_key = $key;
            $_default = $default;
        }

        $this->scope = '';

        $cached = cache("settings:$scope");

        if (!isset($cached[$_key])) {
            $setting = Schema::hasTable($this->model->getTable()) ? $this->model->where([
                'module_name' => $scope,
                'key' => $_key
            ])->get()->first() : null;

            $cached[$_key] = optional($setting)->value ?? $_default;

            cache(["settings:$scope" => $cached]);
        }

        return $cached[$_key];
    }

    /**
     * Set the setting value.
     *
     * An key value array can be passed as key.
     *
     * @param mixed $moduleName
     * @param mixed $key
     * @param mixed $value
     * @return array
     * @throws Exception
     */
    public function set($moduleName, $key, $value = null): array
    {
        if ($this->scope !== '') {
            $scope = $this->scope;
            $_key = $moduleName;
            $_value = $key;
        } else {
            $scope = $moduleName;
            $_key = $key;
            $_value = $value;
        }

        $this->scope = '';

        if (is_array($_key)) {
            $settings = [];

            foreach ($_key as $__key => $_value) {
                $setting = $this->model->updateOrCreate([
                    'module_name' => $scope,
                    'key' => $__key,
                ], [
                    'value' => $_value
                ]);

                $settings = array_merge($settings, $this->prepare($setting));
            }

            cache()->forget("settings:$scope");
            return $settings;
        }

        $setting = $this->model->updateOrCreate([
            'module_name' => $scope,
            'key' => $_key,
        ], [
            'value' => $_value
        ]);

        cache()->forget("settings:$scope");
        return $this->prepare($setting);
    }

    /**
     * @param string $moduleName
     * @throws Exception
     */
    public function flush(string $moduleName): void
    {
        if ($moduleName === null && $this->scope === '') {
            throw new RuntimeException('Scope for settings is missing');
        }

        $scope = $moduleName ?? $this->scope;

        $this->scope = '';

        cache()->forget("settings:$scope");
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
