<?php

namespace App\Helpers;

use App;
use Config;

class EnvUpdater
{
    public static function bulkSet(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $currentContents = file_get_contents(App::environmentFilePath());

        $keyPosition = strpos($currentContents, "/^{$key}=.*/m");

        if ($keyPosition === false) {
            $currentContents .= "\n{$key}={$value}";
        } else {
            $currentContents = preg_replace(
                "/^{$key}=.*/m",
                $key . '=' . $value,
                $currentContents
            );
        }

        file_put_contents(App::environmentFilePath(), $currentContents);

        Config::set($key, $value);
    }
}
