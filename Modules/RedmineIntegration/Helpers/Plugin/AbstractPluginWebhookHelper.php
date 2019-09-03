<?php


namespace Modules\RedmineIntegration\Helpers\Plugin;

use Illuminate\Http\Request;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class AbstractPluginWebhookHelper
 *
 * @package Modules\RedmineIntegration\Helpers\Plugin
 */
abstract class AbstractPluginWebhookHelper
{
    /**
     * @var TaskRepository
     */
    protected $taskRepository;
    /**
     * @var ProjectRepository
     */
    protected $projectRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var Request
     */
    protected $request;

    /**
     * PluginWebhookHelper constructor.
     *
     * @param  TaskRepository     $taskRepository
     * @param  ProjectRepository  $projectRepository
     * @param  UserRepository     $userRepository
     * @param  Request            $request
     */
    public function __construct(
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository,
        UserRepository $userRepository,
        Request $request
    ) {
        $this->taskRepository = $taskRepository;
        $this->projectRepository = $projectRepository;
        $this->userRepository = $userRepository;
        $this->request = $request;
    }

    /**
     * @return mixed|ParameterBag
     */
    protected function getTaskDataFromRequest()
    {
        return $this->request->json('task');
    }

    /**
     * @return mixed|ParameterBag
     */
    protected function getStatusDataFromRequest()
    {
        return $this->request->json('status');
    }

    /**
     * @return mixed|ParameterBag
     */
    protected function getProjectDataFromRequest()
    {
        return $this->request->json('project');
    }

    /**
     * @return mixed|ParameterBag
     */
    protected function getPriorityDataFromRequest()
    {
        return $this->request->json('priority');
    }
}
