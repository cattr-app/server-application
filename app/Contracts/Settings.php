<?php

namespace App\Contracts;

interface Settings
{
    /**
     * @param string $moduleName
     * @return mixed
     */
    public function all(string $moduleName);

    /**
     * @param string $moduleName
     * @param mixed $key
     * @param null $default
     * @return mixed
     */
    public function get(string $moduleName, $key, $default = null);

    /**
     * @param mixed $moduleName
     * @param mixed $key
     * @param null $value
     * @return mixed
     */
    public function set($moduleName, $key, $value = null);

    /**
     * @param string $moduleName
     * @return mixed
     */
    public function flush(string $moduleName);
}
