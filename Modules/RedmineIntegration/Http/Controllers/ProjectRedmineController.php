<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\RedmineIntegration\Helpers\ProjectIntegrationHelper;

class ProjectRedmineController extends AbstractRedmineController
{
    protected ProjectIntegrationHelper $projectIntegrationHelper;

    public function __construct(ProjectIntegrationHelper $projectIntegrationHelper)
    {
        $this->projectIntegrationHelper = $projectIntegrationHelper;

        parent::__construct();
    }

    public static function getControllerRules(): array
    {
        return [
            'synchronize' => 'integration.redmine',
        ];
    }

    /**
     * Synchronize Redmine projects with Cattr projects
     */
    public function synchronize(): JsonResponse
    {
        return new JsonResponse(
            $this->projectIntegrationHelper->synchronizeUserProjects(auth()->id())
        );
    }
}
