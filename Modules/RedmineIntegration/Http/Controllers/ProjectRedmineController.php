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
     * @var ProjectIntegrationHelper
     */
    protected $projectIntegrationHelper;

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'synchronize' => 'integration.redmine',
        ];
    }

    /**
     * ProjectRedmineController constructor.
     *
     * @param  ProjectIntegrationHelper  $projectIntegrationHelper
     */
    public function __construct(ProjectIntegrationHelper $projectIntegrationHelper)
    {
        $this->projectIntegrationHelper = $projectIntegrationHelper;

        parent::__construct();
    }

    /**
     * Synchronize Redmine projects with AmazingTime projects
     *
     * @return JsonResponse
     */
    public function synchronize(): JsonResponse
    {
        return response()->json(
            $this->projectIntegrationHelper->synchronizeUserProjects(auth()->id())
        );
    }
}
