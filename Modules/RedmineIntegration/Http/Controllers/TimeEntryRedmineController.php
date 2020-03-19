<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Modules\RedmineIntegration\Helpers\TimeIntervalIntegrationHelper;

/**
 * Class TimeEntryRedmineController
 */
class TimeEntryRedmineController extends AbstractRedmineController
{
    public static function getControllerRules(): array
    {
        return [
            'create' => 'integration.redmine',
        ];
    }

    /**
     * Send Time Interval to Redmine
     *
     * Upload time interval with id == $timeIntercalId to Redmine by API
     *
     * @throws BindingResolutionException
     */
    public function create(Request $request): void
    {
        $timeIntervalIntegration = app()->make(TimeIntervalIntegrationHelper::class);

        app()->call(
            [
                $timeIntervalIntegration,
                'createInterval'
            ],
            [
                'userId' => auth()->id(),
                'timeIntervalId' => $request->time_interval_id
            ]
        );
    }
}
