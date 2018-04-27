<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\TimeInterval;

/**
 * Class TimeIntervalController
 *
 * @package App\Http\Controllers\Api\v1
 */
class TimeIntervalController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return TimeInterval::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'task_id'  => 'required',
            'start_at'        => 'required',
            'end_at' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'timeinterval';
    }

}
