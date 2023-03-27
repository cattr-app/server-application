<?php

namespace App\Services;

use App\Contracts\SettingsProvider;
use App\Models\Setting;
use Cache;
use Exception;
use PDOException;

class SettingsProviderService implements SettingsProvider
{
    protected string $scope = 'app';

    public function __construct(private readonly Setting $model, private readonly bool $saveScope = true)
    {
    }

    /**
     * Sets scope for the next request to settings module
     *
     * @param string $moduleName
     *
     * @return SettingsProviderService
     */
    public function scope(string $moduleName): SettingsProviderService
    {
        $this->scope = $moduleName;

        return $this;
    }

    /**
     * @inerhitDoc
     */
    final public function all(): array
    {
        $scope = $this->scope;

        if (!$this->saveScope) {
            $this->scope = '';
        }

        $result = $this->model::whereModuleName($scope)
            ->get()
            ->map(static fn(Setting $item) => [$item->key => $item->value])
            ->collapse()
            ->toArray();

        try {
            Cache::store('octane')->forever("settings:$scope", $result);
        } catch (Exception) {
            // DO NOTHING
        }

        return $result;
    }

    /**
     * @inerhitDoc
     */
    final public function get(string $key = null, mixed $default = null): mixed
    {
        $scope = $this->scope;

        if (!$this->saveScope) {
            $this->scope = '';
        }

        try {
            $cached = Cache::store('octane')->get("settings:$scope");

            if (!isset($cached[$key])) {
                $cached[$key] = optional(
                    $this->model::where([
                            'module_name' => $scope,
                            'key' => $key,
                        ])->first()
                )->value ?? $default;

                Cache::store('octane')->put("settings:$scope", $cached);
            }

            return $cached[$key];
        } catch (PDOException) {
            return $default;
        } catch (Exception) {
            return optional(
                $this->model::where([
                        'module_name' => $scope,
                        'key' => $key,
                    ])->first()
            )->value ?? $default;
        }
    }

    /**
     * @inerhitDoc
     */
    final public function set(mixed $key, mixed $value = null, bool $onlyIfNotExists = false): void
    {
        $scope = $this->scope;

        if (!$this->saveScope) {
            $this->scope = '';
        }

        if (is_array($key)) {
            foreach ($key as $_key => $_value) {
                if ($onlyIfNotExists &&
                    $this->model::where([
                        'module_name' => $scope,
                        'key' => $_key,
                    ])->exists()
                ) {
                        continue;
                }

                $this->model::updateOrCreate([
                    'module_name' => $scope,
                    'key' => $_key,
                ], [
                    'value' => $_value,
                ]);
            }
        } else {
            if ($onlyIfNotExists &&
                $this->model::where([
                    'module_name' => $scope,
                    'key' => $key,
                ])->exists()
            ) {
                return;
            }

            $this->model::updateOrCreate([
                'module_name' => $scope,
                'key' => $key,
            ], [
                'value' => $value,
            ]);
        }

        try {
            Cache::store('octane')->forget("settings:$scope");
        } catch (Exception) {
            // DO NOTHING
        }
    }

    /**
     * @inerhitDoc
     */
    final public function flush(): void
    {
        $scope = $this->scope;

        if (!$this->saveScope) {
            $this->scope = '';
        }

        try {
            Cache::store('octane')->forget("settings:$scope");
        } catch (Exception) {
            // DO NOTHING
        }
    }
}
