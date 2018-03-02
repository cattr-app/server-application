<?php

namespace App\Http\Controllers\Api\v1;

use App\User;

class UserController extends ItemController
{
    function getItemClass()
    {
        return User::class;
    }
}

