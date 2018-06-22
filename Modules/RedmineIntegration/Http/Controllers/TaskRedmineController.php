<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\RedmineIntegration\Helpers\TaskIntegrationHelper;

/**
 * Class TaskRedmineController
 *
 * @package Modules\RedmineIntegration\Http\Controllers
 */
class TaskRedmineController extends AbstractRedmineController
{
    /**
     * Synchronize Redmine tasks with AmazingTime tasks
     *
     * @return JsonResponse
     */
    public function synchronize(): JsonResponse
    {
        $taskIntegration = app()->make(TaskIntegrationHelper::class);

        return response()->json(
            app()->call(
                [
                    $taskIntegration,
                    'synchronizeUserTasks'
                ],
                [
                    'userId' => auth()->user()->id
                ]
            )
        );
    }
}
