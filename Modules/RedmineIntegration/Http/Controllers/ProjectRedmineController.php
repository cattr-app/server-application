<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\RedmineIntegration\Helpers\ProjectIntegrationHelper;

/**
 * Class ProjectRedmineController
 *
 * @package Modules\RedmineIntegration\Http\Controllers
 */
class ProjectRedmineController extends AbstractRedmineController
{
    /**
     * Synchronize Redmine projects with AmazingTime projects
     *
     * @param ProjectIntegrationHelper $projectIntegrationHelper
     * @return JsonResponse
     */
    public function synchronize(ProjectIntegrationHelper $projectIntegrationHelper): JsonResponse
    {
        return response()->json(
            $projectIntegrationHelper->synchronizeUserProjects(auth()->user()->id),
            200
        );
    }
}
