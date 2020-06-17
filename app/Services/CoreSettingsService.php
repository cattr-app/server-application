<?php

namespace App\Services;

use App\Contracts\Settings;

class CoreSettingsService
{
    protected const MODULE_NAME = 'core';
    protected Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get all module settings.
     *
     * @return mixed
     */
    public function all(): array
    {
        return $this->settings->all(self::MODULE_NAME);
    }

    /**
     * Set the setting value.
     *
     * An key value array can be passed as key.
     *
     * @param $key
     * @param null $value
     * @return mixed
     */
    public function set($key, $value = null)
    {
        return $this->settings->set(self::MODULE_NAME, $key, $value);
    }

    /**
     * Get the settings value by key.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->settings->get(self::MODULE_NAME, $key, $default);
    }
}
