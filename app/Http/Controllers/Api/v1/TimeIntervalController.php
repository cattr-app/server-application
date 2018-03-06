<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\TimeInterval;

class TimeIntervalController extends ItemController
{
    function getItemClass()
    {
        return TimeInterval::class;
    }

    function getValidationRules()
    {
        return [
            'task_id'  => 'required',
            'start_at'        => 'required',
            'end_at' => 'required',
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'timeinterval';
    }
}
