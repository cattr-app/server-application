<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Task;

class TaskController extends ItemController
{
    function getItemClass()
    {
        return Task::class;
    }

    function getValidationRules()
    {
        return [
            'project_id'  => 'required',
            'task_name'   => 'required',
            'active'      => 'required',
            'user_id'     => 'required',
            'assigned_by' => 'required',
            'url'         => 'required'
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'task';
    }
}
