<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Modules\RedmineIntegration\Helpers\TimeIntervalIntegrationHelper;

/**
 * Class TimeEntryRedmineController
 *
 * @package Modules\RedmineIntegration\Http\Controllers
 */
class TimeEntryRedmineController extends AbstractRedmineController
{
    /**
     * Send Time Interval to Redmine
     *
     * Upload time interval with id == $timeIntercalId to Redmine by API
     *
     * @param Request $request
     */
    public function create(Request $request)
    {
        $timeIntervalIntegration = app()->make(TimeIntervalIntegrationHelper::class);

        app()->call(
            [
                $timeIntervalIntegration,
                'synchronizeUserTasks'
            ],
            [
                'userId'          => auth()->user()->id,
                '$timeIntervalId' => $request->time_interval_id
            ]
        );
    }
}
