<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use Auth;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Route;

/**
 * Class ProjectController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ProjectController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Project::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'name'        => 'required',
            'description' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'project';
    }

    /**
     * @return string[]
     */
    public function getQueryWith(): array
    {
        return ['users'];
    }

    /**
     * @apiDefine ProjectRelations
     *
     * @apiParam {String} [with]               For add relation model in response
     * @apiParam {Object} [tasks] `QueryParam` Project's relation task. All params in <a href="#api-Task-GetTaskList" >@Task</a>
     * @apiParam {Object} [users] `QueryParam` Project's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
    */

    /**
     * @apiDefine ProjectRelationsExample
     * @apiParamExample {json} Request-With-Relations-Example:
     *  {
     *      "with":            "tasks,users,tasks.timeIntervals"
     *      "tasks.id":        [">", 1],
     *      "tasks.active":    1,
     *      "users.full_name": ["like", "%lorem%"]
     *  }
     */

    /**
     * @api {any} /api/v1/projects/list List
     * @apiDescription Get list of Projects
     * @apiVersion 0.1.0
     * @apiName GetProjectList
     * @apiGroup Project
     *
     * @apiParam {Integer}  [id]          `QueryParam` Project ID
     * @apiParam {Integer}  [user_id]     `QueryParam` Project's User ID
     * @apiParam {String}   [name]        `QueryParam` Project's name
     * @apiParam {String}   [description] `QueryParam` Project's description
     * @apiParam {DateTime} [created_at]  `QueryParam` Project's date time of create
     * @apiParam {DateTime} [updated_at]  `QueryParam` Project's date time of update
     * @apiUse ProjectRelations
     *
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "id":          [">", 1]
     *      "user_id":     ["=", [1,2,3]],
     *      "name":        ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse ProjectRelationsExample
     *
     * @apiSuccess {Object[]} ProjectList                     Projects (Array of objects)
     * @apiSuccess {Object}   ProjectList.Project             Project object
     * @apiSuccess {Integer}  ProjectList.Project.id          Project's ID
     * @apiSuccess {String}   ProjectList.Project.name        Project's name
     * @apiSuccess {String}   ProjectList.Project.description Project's description
     * @apiSuccess {DateTime} ProjectList.Project.created_at  Project's date time of create
     * @apiSuccess {DateTime} ProjectList.Project.updated_at  Project's date time of update
     * @apiSuccess {DateTime} ProjectList.Project.deleted_at  Project's date time of delete
     * @apiSuccess {Object[]} ProjectList.Project.users       Project's Users (Array of objects)
     * @apiSuccess {Object[]} ProjectList.Project.tasks       Project's Tasks (Array of objects)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $project_relations_access = Role::can(Auth::user(), 'projects', 'relations');
        $full_access = Role::can(Auth::user(), 'projects', 'full_access');
        $direct_relation = $request->get('direct_relation') ? $request->get('direct_relation') : false;
        $request->offsetUnset('direct_relation');

        if ($direct_relation) {
            $projects = collect(Auth::user()->projects)->pluck('id')->toArray();
            if (count($projects) > 0) {
                $request->offsetSet('id', ['=', $projects]);
            }
        }

        if ($project_relations_access && $request->get('user_id')) {
            $usersId = collect($request->get('user_id'))->flatten(0)->filter(function($val) {
                return is_int($val);
            });
            $attachedUsersId = collect(Auth::user()->projects)->flatMap(function($project) {
                return collect($project->users)->pluck('id');
            });

            if (!collect($attachedUsersId)->contains($usersId->all()) && !$full_access) {
                return response()->json(Filter::process(
                    $this->getEventUniqueName('answer.success.item.relations'),
                    []
                ));
            }

            /** show all projects for full access if id in request === user->id */
            if (collect($usersId)->contains(Auth::user()->id) && $full_access) {
                true;
            } else {
                $request->offsetSet('users.id', $request->get('user_id'));
                if (!$direct_relation) {
                    $request->offsetSet('tasks.user_id', $request->get('user_id'));
                    $request->offsetSet('tasks.timeIntervals.user_id', $request->get('user_id'));
                }
            }
            $request->offsetUnset('user_id');
        }

        return parent::index($request);
    }


    /**
     * @api {post} /api/v1/projects/create Create
     * @apiDescription Create Project
     * @apiVersion 0.1.0
     * @apiName CreateProject
     * @apiGroup Project
     *
     * @apiParam {String}  name         Project's name
     * @apiParam {String}  description  Project's description
     *
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "name": "test",
     *      "description": "test"
     *  }
     *
     * @apiSuccess {Object}   res             Response object
     * @apiSuccess {Integer}  res.id          Project's ID
     * @apiSuccess {String}   res.name        Project's name
     * @apiSuccess {String}   res.description Project's description
     * @apiSuccess {DateTime} res.created_at  Project's date time of create
     * @apiSuccess {DateTime} res.updated_at  Project's date time of update
     *
     * @apiUse DefaultCreateErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {any} /api/v1/projects/show Show
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "id":          1,
     *      "user_id":     ["=", [1,2,3]],
     *      "name":        ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse ProjectRelationsExample
     * @apiDescription Show Project
     * @apiVersion 0.1.0
     * @apiName ShowProject
     * @apiGroup Project
     *
     * @apiParam {Integer}  id            `QueryParam` Project ID
     * @apiParam {Integer}  [user_id]     `QueryParam` Project's User ID
     * @apiParam {String}   [name]        `QueryParam` Project's name
     * @apiParam {String}   [description] `QueryParam` Project's description
     * @apiParam {DateTime} [created_at]  `QueryParam` Project's date time of create
     * @apiParam {DateTime} [updated_at]  `QueryParam` Project's date time of update
     * @apiUse ProjectRelations
     *
     * @apiSuccess {Object}   Project             Project object
     * @apiSuccess {Integer}  Project.id          Project's ID
     * @apiSuccess {String}   Project.name        Project's name
     * @apiSuccess {String}   Project.description Project's description
     * @apiSuccess {DateTime} Project.created_at  Project's date time of create
     * @apiSuccess {DateTime} Project.updated_at  Project's date time of update
     * @apiSuccess {DateTime} Project.deleted_at  Project's date time of delete
     * @apiSuccess {Object[]} Project.users       Project's User (Array of objects)
     * @apiSuccess {Object[]} Project.tasks       Project's Task (Array of objects)
     *
     * @apiUse DefaultShowErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/projects/edit Edit
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "id": 1,
     *      "name": "test",
     *      "description": "test"
     *  }
     * @apiDescription Edit Project
     * @apiVersion 0.1.0
     * @apiName EditProject
     * @apiGroup Project
     *
     * @apiParam {String}  id           Project's id
     * @apiParam {String}  name         Project's name
     * @apiParam {String}  description  Project's description
     *
     * @apiSuccess {Object}   res             Response object
     * @apiSuccess {Integer}  res.id          Project's ID
     * @apiSuccess {String}   res.name        Project's name
     * @apiSuccess {String}   res.description Project's description
     * @apiSuccess {DateTime} res.created_at  Project's date time of create
     * @apiSuccess {DateTime} res.updated_at  Project's date time of update
     * @apiSuccess {DateTime} res.deleted_at  Project's date time of delete
     *
     * @apiUse DefaultEditErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/projects/destroy Destroy
     * @apiUse DefaultDestroyRequestExample
     * @apiDescription Destroy Project
     * @apiVersion 0.1.0
     * @apiName DestroyProject
     * @apiGroup Project
     *
     * @apiParam {String} id Project's id
     *
     * @apiUse DefaultDestroyResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = false): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'projects', 'full_access');
        $user_relations_access = Role::can(Auth::user(), 'users', 'relations');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $user_projects_id = collect(Auth::user()->projects)->pluck('id');
        $projects_id = collect($user_projects_id);

        /** edit and remove only for directly related users's project */
        if ($action_method !== 'edit' && $action_method !== 'remove') {

            if (count($projects_id) <= 0) {
                return $query;
            }

            $user_tasks_project_id = collect(Auth::user()->tasks)->flatMap(function ($task) {
                if (isset($task->project)) {
                    return collect($task->project->id);
                }
                return null;
            });
            $user_time_interval_project_id = collect(Auth::user()->timeIntervals)->flatMap(function ($val) {
                if (isset($val->task->project)) {
                    return collect($val->task->project->id);
                }
                return null;
            });
            $projects_id = collect([$projects_id, $user_tasks_project_id, $user_time_interval_project_id])->collapse();
        }

        if ($user_relations_access) {
            $attached_users_project_id = collect(Auth::user()->attached_users)->flatMap(function($user) {
                return collect($user->projects)->pluck('id');
            });
            $projects_id = collect([$projects_id, $attached_users_project_id])->collapse()->unique();
        }

        $query->whereIn('projects.id', $projects_id);
        return $query;
    }
}
