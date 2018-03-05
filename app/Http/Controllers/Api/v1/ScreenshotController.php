<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Screenshot;

class ScreenshotController extends ItemController
{
    function getItemClass()
    {
        return Screenshot::class;
    }

    function getValidationRules()
    {
        return [
            'time_interval_id'  => 'required',
            'name'        => 'required',
            'path' => 'required',
        ];
    }
}
