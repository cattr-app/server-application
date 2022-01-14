<?php

namespace App\Contracts;

interface SettingsProvider
{
    /**
     * Get all module settings
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get the settings value by key
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set the setting value
     * A key value array can be passed as key
     *
     * @param string     $key
     * @param mixed|null $value
     *
     * @return void
     */
    public function set(string $key, mixed $value = null): void;

    /**
     * Flushes module settings storage
     *
     * @return void
     */
    public function flush(): void;
}
