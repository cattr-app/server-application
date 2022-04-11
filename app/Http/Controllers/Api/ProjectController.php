<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\CreateProjectRequestCattr;
use App\Http\Requests\Project\EditProjectRequestCattr;
use App\Http\Requests\Project\DestroyProjectRequestCattr;
use App\Http\Requests\Project\ShowProjectRequestCattr;
use Filter;
use App\Models\Project;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;

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
        return [];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'project';
    }

    /**
     * @api             {get, post} /projects/list List
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
        return $this->_index($request);
    }

    /**
     * @throws Exception
     */
    public function show(ShowProjectRequestCattr $request): JsonResponse
    {
        Filter::listen(Filter::getSuccessResponseFilterName(), static function ($project) {
            $totalTracked = 0;
            $taskIDs = array_map(static function ($task) {
                return $task['id'];
            }, $project['tasks']);

            $workers = DB::table('time_intervals AS i')
                ->leftJoin('tasks AS t', 'i.task_id', '=', 't.id')
                ->leftJoin('users AS u', 'i.user_id', '=', 'u.id')
                ->select(
                    'i.user_id',
                    'u.full_name',
                    'i.task_id',
                    'i.start_at',
                    'i.end_at',
                    't.task_name',
                    DB::raw('SUM(TIMESTAMPDIFF(SECOND, i.start_at, i.end_at)) as duration')
                )
                ->whereNull('i.deleted_at')
                ->whereIn('task_id', $taskIDs)
                ->orderBy('duration', 'desc')
                ->groupBy('t.id')
                ->get();

            foreach ($workers as $worker) {
                $totalTracked += $worker->duration;
            }

            $project['workers'] = $workers;
            $project['total_spent_time'] = $totalTracked;
            return $project;
        });

        Filter::listen(Filter::getQueryPrepareFilterName(), static fn($query) => $query->with('tasks'));

        return $this->_show($request);
    }

    /**
     * @api            {post} /projects/create Create
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
     * @apiSuccess {Object}   res             Response
     *
     * @apiUse          ProjectObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
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
    public function create(CreateProjectRequestCattr $request): JsonResponse
    {
        Filter::listen($this->getEventUniqueName('item.create'), static function (Project $project) use ($request) {
            if ($request->has('statuses')) {
                $statuses = [];
                foreach ($request->get('statuses') as $status) {
                    $statuses[$status['id']] = ['color' => $status['color']];
                }

                $project->statuses()->sync($statuses);
            }

            return $project;
        });

        Filter::listen(Filter::getSuccessResponseFilterName(), static fn($data) => $data->load('statuses'));

        return $this->_create($request);
    }

    /**
     * @api             {get, post} /projects/show Show
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
     *            "activity_fill": 88,
     *            "mouse_fill": 40,
     *            "keyboard_fill": 48,
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
     *            "activity_fill": 88,
     *            "mouse_fill": 40,
     *            "keyboard_fill": 48,
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
     * @throws Exception
     * @api             {post} /projects/edit Edit
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
     *
     * @apiUse          ProjectObject
     *
     * @apiSuccessExample {json} Response Example
     *  {
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
    public function edit(EditProjectRequestCattr $request): JsonResponse
    {
        Filter::listen($this->getEventUniqueName('item.edit'), static function (Project $project) use ($request) {
            if ($request->has('statuses')) {
                $statuses = [];
                foreach ($request->get('statuses') as $status) {
                    $statuses[$status['id']] = ['color' => $status['color']];
                }

                $project->statuses()->sync($statuses);
            }

            return $project;
        });

        Filter::listen(Filter::getSuccessResponseFilterName(), static fn($data) => $data->load('statuses'));

        return $this->_edit($request);
    }

    /**
     * @throws Exception
     * @api             {post} /projects/remove Destroy
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
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     * @apiUse          ItemNotFoundError
     */
    public function destroy(DestroyProjectRequestCattr $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @throws Exception
     * @api             {get,post} /projects/count Count
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
     * @apiSuccess {String}   total    Amount of projects that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function count(Request $request): JsonResponse
    {
        return $this->_count($request);
    }
}
