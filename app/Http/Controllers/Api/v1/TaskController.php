<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Task;

class TaskController extends ItemController
{
    function getItemClass()
    {
        return Task::class;
    }
}
