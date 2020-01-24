<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\EventFilter\Facades\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
            'name' => 'required',
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
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'projects.list',
            'count' => 'projects.list',
            'create' => 'projects.create',
            'edit' => 'projects.edit',
            'show' => 'projects.show',
            'destroy' => 'projects.remove',
            'tasks' => 'projects.tasks',
        ];
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
     * @apiParamExample {json} Request With Relations Example
     *  {
     *      "with":            "tasks,users,tasks.timeIntervals",
     *      "tasks.id":        [">", 1],
     *      "tasks.active":    1,
     *      "users.full_name": ["like", "%lorem%"],
     *      "id":              1
     *  }
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {get, post} /api/v1/projects/list List
     * @apiDescription Get list of Projects
     * @apiVersion     0.1.0
     * @apiName        GetProjectList
     * @apiGroup       Project
     *
     * @apiParam {Integer}  [id]          `QueryParam` Project id
     * @apiParam {Integer}  [user_id]     `QueryParam` Project User id
     * @apiParam {String}   [name]        `QueryParam` Project name
     * @apiParam {String}   [description] `QueryParam` Project description
     * @apiParam {String}   [created_at]  `QueryParam` Project date time of create
     * @apiParam {String}   [updated_at]  `QueryParam` Project date time of update
     *
     * @apiUse         ProjectRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          [">", 1]
     *      "user_id":     ["=", [1,2,3]],
     *      "name":        ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse         ProjectRelationsExample
     * @apiUse         UnauthorizedError
     *
     * @apiSuccess {Object[]} ProjectList                     Projects
     * @apiSuccess {Object}   ProjectList.Project             Project
     * @apiSuccess {Integer}  ProjectList.Project.id          Project id
     * @apiSuccess {String}   ProjectList.Project.name        Project name
     * @apiSuccess {String}   ProjectList.Project.description Project description
     * @apiSuccess {String}   ProjectList.Project.created_at  Project date time of create
     * @apiSuccess {String}   ProjectList.Project.updated_at  Project date time of update
     * @apiSuccess {String}   ProjectList.Project.deleted_at  Project date time of delete
     * @apiSuccess {Object[]} ProjectList.Project.users       Project Users
     * @apiSuccess {Object[]} ProjectList.Project.tasks       Project Tasks
     *
     * @apiSuccessExample {json} Answer Example
     * [
     *   {
     *     "id": 1,
     *     "company_id": 0,
     *     "name": "Eos est amet sunt ut autem harum.",
     *     "description": "Dolores rem et sed beatae...",
     *     "deleted_at": null,
     *     "created_at": "2018-09-25 06:15:08",
     *     "updated_at": "2018-09-25 06:15:08"
     *   },
     *   {
     *     "id": 2,
     *     "company_id": 1,
     *     "name": "Incidunt officiis.",
     *     "description": "Quas quam sint vero...",
     *     "deleted_at": null,
     *     "created_at": "2018-09-25 06:15:11",
     *     "updated_at": "2018-09-25 06:15:11"
     *   }
     * ]
     *
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
            $usersId = collect($request->get('user_id'))->flatten(0)->filter(function ($val) {
                return is_int($val);
            });
            $attachedUsersId = collect(Auth::user()->projects)->flatMap(function ($project) {
                return collect($project->users)->pluck('id');
            });

            if (!collect($attachedUsersId)->contains($usersId->all()) && !$full_access) {
                // Add filter by projects attached to the current user for the indirectly related projects.
                $projects = collect(Auth::user()->projects)->pluck('id')->toArray();
                if (count($projects) > 0) {
                    $request->offsetSet('tasks.project_id', ['=', $projects]);
                }
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
     * Returns tasks info for a project.
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function tasks(Request $request): JsonResponse
    {
        $itemId = is_int($request->get('id')) ? $request->get('id') : false;

        if (!$itemId) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'Invalid id',
                ]), 400);
        }

        $user = Auth::user();
        $userProjectIds = Project::getUserRelatedProjectIds($user);
        if (!in_array($itemId, $userProjectIds)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'success' => false,
                    'error_type' => 'authorization.forbidden',
                    'message' => 'User has no access to this project',
                ]), 403);
        }

        $project_info = DB::table('project_report')
            ->select(
                DB::raw('MIN(date) as start'),
                DB::raw('MAX(date) as end'),
                DB::raw('SUM(duration) as duration')
            )
            ->where([
                ['project_id', '=', $itemId],
            ])
            ->first();

        $tasks_query = DB::table('tasks')
            ->leftJoin('project_report', 'tasks.id', '=', 'project_report.task_id')
            ->select(
                DB::raw('tasks.id as id'),
                DB::raw('tasks.task_name as task_name'),
                DB::raw('MIN(project_report.date) as start'),
                DB::raw('MAX(project_report.date) as end'),
                DB::raw('SUM(project_report.duration) as duration')
            )
            ->where([
                ['tasks.project_id', '=', $itemId],
            ])
            ->groupBy(
                'id',
                'task_name'
            );

        if ($request->has('order_by')) {
            $order_by = $request->get('order_by');
            [$column, $dir] = is_array($order_by) ? $order_by : [$order_by, 'asc'];
            if (in_array($column, [
                'id',
                'task_name',
                'start',
                'end',
                'duration',
            ])) {
                $tasks_query->orderBy($column, $dir);
            } else {
                $tasks_query->orderBy('id', 'asc');
            }
        } else {
            $tasks_query->orderBy('id', 'asc');
        }

        $project_info->tasks = $tasks_query->get();

        return response()->json(['success' => true, 'res' => $project_info]);
    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/projects/create Create
     * @apiDescription Create Project
     * @apiVersion     0.1.0
     * @apiName        CreateProject
     * @apiGroup       Project
     *
     * @apiParam {String}  name         Project name
     * @apiParam {String}  description  Project description
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "name": "SampleOriginalProjectName",
     *      "description": "Code-monkey development group presents"
     *  }
     *
     * @apiSuccess {Object}   res             Response
     * @apiSuccess {Integer}  res.id          Project id
     * @apiSuccess {String}   res.name        Project name
     * @apiSuccess {String}   res.description Project description
     * @apiSuccess {String}   res.created_at  Project date time of create
     * @apiSuccess {String}   res.updated_at  Project date time of update
     *
     * @apiUse         DefaultCreateErrorResponse
     * @apiUse         UnauthorizedError
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "res": {
     *     "name": "SampleOriginalProjectName",
     *     "description": "Code-monkey development group presents",
     *     "updated_at": "2018-09-27 04:55:29",
     *     "created_at": "2018-09-27 04:55:29",
     *     "id": 6
     *   }
     * }
     *
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/projects/show Show
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          1,
     *      "user_id":     ["=", [1,2,3]],
     *      "name":        ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse         ProjectRelationsExample
     * @apiDescription Show Project
     * @apiVersion     0.1.0
     * @apiName        ShowProject
     * @apiGroup       Project
     *
     * @apiParam {Integer}  id            `QueryParam` Project id
     * @apiParam {Integer}  [user_id]     `QueryParam` Project User id
     * @apiParam {String}   [name]        `QueryParam` Project name
     * @apiParam {String}   [description] `QueryParam` Project description
     * @apiParam {String}   [created_at]  `QueryParam` Project date time of create
     * @apiParam {String}   [updated_at]  `QueryParam` Project date time of update
     * @apiUse         ProjectRelations
     *
     * @apiSuccess {Object}   Project             Project object
     * @apiSuccess {Integer}  Project.id          Project id
     * @apiSuccess {String}   Project.name        Project name
     * @apiSuccess {String}   Project.description Project description
     * @apiSuccess {String}   Project.created_at  Project date time of create
     * @apiSuccess {String}   Project.updated_at  Project date time of update
     * @apiSuccess {String}   Project.deleted_at  Project date time of delete
     * @apiSuccess {Object[]} Project.users       Project Users
     * @apiSuccess {Object[]} Project.tasks       Project Tasks
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "id": 1,
     *   "company_id": 0,
     *   "name": "Eos est amet sunt ut autem harum.",
     *   "description": "Dolores rem et sed beatae architecto...",
     *   "deleted_at": null,
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08"
     * }
     *
     * @apiSuccessExample {json} Answer Relation Example
     * {
     *   "id": 1,
     *   "company_id": 0,
     *   "name": "Eos est amet sunt ut autem harum.",
     *   "description": "Dolores rem et sed beatae architecto assumenda illum reprehenderit...",
     *   "deleted_at": null,
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08",
     *   "tasks": [
     *   {
     *   "id": 1,
     *   "project_id": 1,
     *   "task_name": "Enim et sit similique.",
     *   "description": "Adipisci eius qui quia et rerum rem perspiciatis...",
     *   "active": 1,
     *   "user_id": 1,
     *   "assigned_by": 1,
     *   "url": null,
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08",
     *   "deleted_at": null,
     *   "time_intervals": [
     *     {
     *       "id": 1,
     *       "task_id": 1,
     *       "start_at": "2006-05-31 16:15:09",
     *       "end_at": "2006-05-31 16:20:07",
     *       "created_at": "2018-09-25 06:15:08",
     *       "updated_at": "2018-09-25 06:15:08",
     *       "deleted_at": null,
     *       "count_mouse": 88,
     *       "count_keyboard": 127,
     *       "user_id": 1
     *     },
     *     {
     *       "id": 2,
     *       "task_id": 1,
     *       "start_at": "2006-05-31 16:20:08",
     *       "end_at": "2006-05-31 16:25:06",
     *       "created_at": "2018-09-25 06:15:08",
     *       "updated_at": "2018-09-25 06:15:08",
     *       "deleted_at": null,
     *       "count_mouse": 117,
     *       "count_keyboard": 23,
     *       "user_id": 1
     *     }
     *   ]
     * }
     *
     * @apiUse         DefaultShowErrorResponse
     * @apiUse         UnauthorizedError
     *
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {put, post} /api/v1/projects/edit Edit
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id": 1,
     *      "name": "test",
     *      "description": "test"
     *  }
     *
     * @apiDescription Edit Project
     * @apiVersion     0.1.0
     * @apiName        EditProject
     * @apiGroup       Project
     *
     * @apiParam {String}  id           Project id
     * @apiParam {String}  name         Project name
     * @apiParam {String}  description  Project description
     *
     * @apiSuccess {Object}   res             Response object
     * @apiSuccess {Integer}  res.id          Project id
     * @apiSuccess {String}   res.name        Project name
     * @apiSuccess {String}   res.description Project description
     * @apiSuccess {String}   res.created_at  Project date time of create
     * @apiSuccess {String}   res.updated_at  Project date time of update
     * @apiSuccess {String}   res.deleted_at  Project date time of delete
     *
     * @apiUse         DefaultEditErrorResponse
     * @apiUse         UnauthorizedError
     *
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {delete, post} /api/v1/projects/remove Destroy
     * @apiUse         DefaultDestroyRequestExample
     * @apiDescription Destroy Project
     * @apiVersion     0.1.0
     * @apiName        DestroyProject
     * @apiGroup       Project
     *
     * @apiParam {String} id Project id
     *
     * @apiUse         DefaultDestroyResponse
     * @apiUse         UnauthorizedError
     *
     */

    /**
     * @param  bool  $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        $user = Auth::user();
        $user_id = $user->id;
        $query = parent::getQuery($withRelations, $withSoftDeleted);
        $full_access = Role::can($user, 'projects', 'full_access');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $rules = $this->getControllerRules();
        $rule = $rules[$action_method] ?? null;
        if (isset($rule)) {
            [$object, $action] = explode('.', $rule);
            // Check user default role
            if (Role::can($user, $object, $action)) {
                return $query;
            }

            $query->where(function (Builder $query) use ($user_id, $object, $action) {
                // Filter by project roles of the user
                $query->whereHas('usersRelation', static function (Builder $query) use ($user_id, $object, $action) {
                    $query->where('user_id', $user_id)->whereHas('role', static function (Builder $query) use ($object, $action) {
                        $query->whereHas('rules', static function (Builder $query) use ($object, $action) {
                            $query->where([
                                'object' => $object,
                                'action' => $action,
                                'allow'  => true,
                            ])->select('id');
                        })->select('id');
                    })->select('id');
                });

                // For read-only access include projects where the user have assigned tasks or tracked intervals
                $query->when($action !== 'edit' && $action !== 'remove', static function (Builder $query) use ($user_id) {
                    $query->orWhereHas('tasks', static function (Builder $query) use ($user_id) {
                        $query->where('user_id', $user_id)->select('user_id');
                    });

                    $query->orWhereHas('tasks.timeIntervals', static function (Builder $query) use ($user_id) {
                        $query->where('user_id', $user_id)->select('user_id');
                    });
                });
            });
        }

        return $query;
    }
}
