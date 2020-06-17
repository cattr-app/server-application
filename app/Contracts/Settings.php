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
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $moduleName, string $key, $default = null);

    /**
     * @param string $moduleName
     * @param $key
     * @param null $value
     * @return mixed
     */
    public function set(string $moduleName, $key, $value = null);
}
