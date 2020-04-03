<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\RedmineIntegration\Helpers\TaskIntegrationHelper;

/**
 * Class TaskRedmineController
 */
class TaskRedmineController extends AbstractRedmineController
{
    public static function getControllerRules(): array
    {
        return [
            'synchronize' => 'integration.redmine',
        ];
    }

    /**
     * Synchronize Redmine tasks with Cattr tasks
     */
    public function synchronize(): JsonResponse
    {
        $taskIntegration = app()->make(TaskIntegrationHelper::class);

        return new JsonResponse(
            app()->call(
                [
                    $taskIntegration,
                    'synchronizeUserTasks'
                ],
                [
                    'userId' => auth()->id()
                ]
            )
        );
    }
}
