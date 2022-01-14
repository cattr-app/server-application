<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SettingsFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
