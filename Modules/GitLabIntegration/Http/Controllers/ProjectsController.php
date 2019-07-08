<?php

namespace Modules\GitLabIntegration\Http\Controllers;

use Filter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\GitLabIntegration\Helpers\GitLabProjects;

class ProjectsController extends Controller
{
    /**
     * @var GitLabProjects
     */
    protected $projects;

    public function __construct(
        GitLabProjects $projects
    ) {
        $this->projects = $projects;
    }

    public function list(Request $request)
    {
        $request = Filter::process('request.gitlab.projects.list', $request);
        $userId = $request->user()->id;
        $projects = $this->projects->getAll($userId);

        return response()->json($projects);
    }
}
