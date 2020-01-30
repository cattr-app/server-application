<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Exception;
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
     * @api             {get, post} /v1/projects/list List
     * @apiDescription  Get list of Projects
     *
     * @apiVersion      1.0.0
     * @apiName         GetProjectList
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_list
     * @apiPermission   projects_full_access
     *
     * @apiUse         ProjectParams
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
     *
     * @apiUse          ProjectObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 1,
     *      "company_id": 0,
     *      "name": "Eos est amet sunt ut autem harum.",
     *      "description": "Dolores rem et sed beatae...",
     *      "deleted_at": null,
     *      "created_at": "2018-09-25 06:15:08",
     *      "updated_at": "2018-09-25 06:15:08"
     *    },
     *    {
     *      "id": 2,
     *      "company_id": 1,
     *      "name": "Incidunt officiis.",
     *      "description": "Quas quam sint vero...",
     *      "deleted_at": null,
     *      "created_at": "2018-09-25 06:15:11",
     *      "updated_at": "2018-09-25 06:15:11"
     *    }
     *  ]
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
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
     * @apiDeprecated   since 1.0.0
     * @api             {get,post} /v1/projects/tasks Tasks
     * @apiDescription  Get tasks that assigned to project
     *
     * @apiVersion      1.0.0
     * @apiName         ProjectTasks
     * @apiGroup        Project
     *
     * @apiPermission   projects_tasks
     * @apiPermission   projects_full_access
     */
    /**
     * Returns tasks info for a project.
     *
     * @param Request $request
     * @deprecated
     * @codeCoverageIgnore
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

        /* @var User $user */
        $user = auth()->user();
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
     * @api            {post} /v1/projects/create Create
     * @apiDescription Create Project
     *
     * @apiVersion     1.0.0
     * @apiName        CreateProject
     * @apiGroup       Project
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_create
     * @apiPermission   projects_full_access
     *
     * @apiParam {String}  name         Project name
     * @apiParam {String}  description  Project description
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "name": "SampleOriginalProjectName",
     *      "description": "Code-monkey development group presents"
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res             Response
     *
     * @apiUse          ProjectObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": {
     *      "name": "SampleOriginalProjectName",
     *      "description": "Code-monkey development group presents",
     *      "updated_at": "2018-09-27 04:55:29",
     *      "created_at": "2018-09-27 04:55:29",
     *      "id": 6
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */

    /**
     * @api             {get, post} /v1/projects/show Show
     * @apiDescription  Show Project
     *
     * @apiVersion      1.0.0
     * @apiName         ShowProject
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_show
     * @apiPermission   projects_full_access
     *
     * @apiParam {Integer}  id  Project ID
     *
     * @apiUse          ProjectParams
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "id":          1,
     *      "user_id":     ["=", [1,2,3]],
     *      "name":        ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse         ProjectObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "id": 1,
     *    "company_id": 0,
     *    "name": "Eos est amet sunt ut autem harum.",
     *    "description": "Dolores rem et sed beatae architecto...",
     *    "deleted_at": null,
     *    "created_at": "2018-09-25 06:15:08",
     *    "updated_at": "2018-09-25 06:15:08"
     *  }
     *
     * @apiSuccessExample {json} Response Relation Example
     *  HTTP/1.1 200 OK
     *  {
     *    "id": 1,
     *    "company_id": 0,
     *    "name": "Eos est amet sunt ut autem harum.",
     *    "description": "Dolores rem et sed beatae architecto assumenda illum reprehenderit...",
     *    "deleted_at": null,
     *    "created_at": "2018-09-25 06:15:08",
     *    "updated_at": "2018-09-25 06:15:08",
     *    "tasks": [
     *      {
     *        "id": 1,
     *        "project_id": 1,
     *        "task_name": "Enim et sit similique.",
     *        "description": "Adipisci eius qui quia et rerum rem perspiciatis...",
     *        "active": 1,
     *        "user_id": 1,
     *        "assigned_by": 1,
     *        "url": null,
     *        "created_at": "2018-09-25 06:15:08",
     *        "updated_at": "2018-09-25 06:15:08",
     *        "deleted_at": null,
     *        "time_intervals": [
     *          {
     *            "id": 1,
     *            "task_id": 1,
     *            "start_at": "2006-05-31 16:15:09",
     *            "end_at": "2006-05-31 16:20:07",
     *            "created_at": "2018-09-25 06:15:08",
     *            "updated_at": "2018-09-25 06:15:08",
     *            "deleted_at": null,
     *            "count_mouse": 88,
     *            "count_keyboard": 127,
     *            "user_id": 1
     *          },
     *          {
     *            "id": 2,
     *            "task_id": 1,
     *            "start_at": "2006-05-31 16:20:08",
     *            "end_at": "2006-05-31 16:25:06",
     *            "created_at": "2018-09-25 06:15:08",
     *            "updated_at": "2018-09-25 06:15:08",
     *            "deleted_at": null,
     *            "count_mouse": 117,
     *            "count_keyboard": 23,
     *            "user_id": 1
     *          }
     *      }
     *    ]
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     * @apiUse         ItemNotFoundError
     */

    /**
     * @api             {post} /v1/projects/edit Edit
     * @apiDescription  Edit Project
     *
     * @apiVersion      1.0.0
     * @apiName         EditProject
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_edit
     * @apiPermission   projects_full_access
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "id": 1,
     *      "name": "test",
     *      "description": "test"
     *  }
     *
     * @apiParam {String}  id           Project id
     * @apiParam {String}  name         Project name
     * @apiParam {String}  description  Project description
     *
     * @apiSuccess {Object}   res      Response object
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     *
     * @apiUse          ProjectObject
     *
     * @apiSuccessExample {json} Response Example
     *  {
     *    "success": true,
     *    "res": {
     *      "id": 1,
     *      "company_id": 0,
     *      "name": "Eos est amet sunt ut autem harum.",
     *      "description": "Dolores rem et sed beatae architecto...",
     *      "deleted_at": null,
     *      "created_at": "2018-09-25 06:15:08",
     *      "updated_at": "2018-09-25 06:15:08"
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */

    /**
     * @api             {post} /v1/projects/remove Destroy
     * @apiDescription  Destroy Project
     *
     * @apiVersion      1.0.0
     * @apiName         DestroyProject
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_remove
     * @apiPermission   projects_full_access
     *
     * @apiParam {String} id Project id
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     * @apiUse          ItemNotFoundError
     */

    /**
     * @api             {get,post} /v1/projects/count Count
     * @apiDescription  Count Projects
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_count
     * @apiPermission   projects_full_access
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   total    Amount of projects that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @param bool $withRelations
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
                    $query->where('user_id', $user_id)->whereHas('role',
                        static function (Builder $query) use ($object, $action) {
                            $query->whereHas('rules', static function (Builder $query) use ($object, $action) {
                                $query->where([
                                    'object' => $object,
                                    'action' => $action,
                                    'allow' => true,
                                ])->select('id');
                            })->select('id');
                        })->select('id');
                });

                // For read-only access include projects where the user have assigned tasks or tracked intervals
                $query->when($action !== 'edit' && $action !== 'remove',
                    static function (Builder $query) use ($user_id) {
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
