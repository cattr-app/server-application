<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\EditProjectRequest;
use App\Http\Requests\Project\DestroyProjectRequest;
use App\Http\Requests\Project\GanttDataRequest;
use App\Http\Requests\Project\ListProjectRequest;
use App\Http\Requests\Project\PhasesRequest;
use App\Http\Requests\Project\ShowProjectRequest;
use CatEvent;
use Filter;
use App\Models\Project;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use DB;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder as AdjacencyListBuilder;
use Throwable;

class ProjectController extends ItemController
{
    protected const MODEL = Project::class;

    /**
     * @api             {get, post} /projects/list List
     * @apiDescription  Get list of Projects
     *
     * @apiVersion      4.0.0
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
     *      "id":          [">", 1],
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
     *     {
     *       "id": 1,
     *       "company_id": 1,
     *       "name": "Dolores voluptates.",
     *       "description": "Deleniti maxime fugit nesciunt. Ut maiores deleniti tempora vel. Nisi aut doloremque accusantium tempore aut.",
     *       "deleted_at": null,
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2023-10-26T10:26:17.000000Z",
     *       "important": 1,
     *       "source": "internal",
     *       "default_priority_id": null
     *     },
     *     {
     *       "id": 2,
     *       "company_id": 5,
     *       "name": "Et veniam velit tempore.",
     *       "description": "Consequatur nulla distinctio reprehenderit rerum omnis debitis. Fugit illum ratione quia harum. Optio porro consequatur enim esse.",
     *       "deleted_at": null,
     *       "created_at": "2023-10-26T10:26:42.000000Z",
     *       "updated_at": "2023-10-26T10:26:42.000000Z",
     *       "important": 1,
     *       "source": "internal",
     *       "default_priority_id": null
     *     }
     *  ]
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */
    /**
     * @param ListProjectRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ListProjectRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * @api             {get} /projects/gantt-data Gantt Data
     * @apiDescription  Получение данных для диаграммы Ганта по проекту
     *
     * @apiVersion      4.0.0
     * @apiName         GetGanttData
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer} id  ID проекта
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1
     *  }
     *
     * @apiSuccess {Integer}  id                   ID проекта
     * @apiSuccess {Integer}  company_id           ID компании
     * @apiSuccess {String}   name                 Название проекта
     * @apiSuccess {String}   description          Описание проекта
     * @apiSuccess {String}   deleted_at           Дата удаления (null, если не удален)
     * @apiSuccess {String}   created_at           Дата создания
     * @apiSuccess {String}   updated_at           Дата обновления
     * @apiSuccess {Integer}  important            Важность проекта (1 - важный, 0 - не важный)
     * @apiSuccess {String}   source               Источник проекта (internal/external)
     * @apiSuccess {Integer}  default_priority_id  ID приоритета по умолчанию (null, если нет)
     * @apiSuccess {Object[]} tasks_relations      Связи задач
     * @apiSuccess {Integer}  tasks_relations.parent_id  ID родительской задачи
     * @apiSuccess {Integer}  tasks_relations.child_id   ID дочерней задачи
     * @apiSuccess {Object[]} tasks                Список задач
     * @apiSuccess {Object[]} phases               Список фаз проекта
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 1,
     *      "company_id": 1,
     *      "name": "Dolores voluptates.",
     *      "description": "Deleniti maxime fugit nesciunt. Ut maiores deleniti tempora vel. Nisi aut doloremque accusantium tempore aut.",
     *      "deleted_at": null,
     *      "created_at": "2023-10-26T10:26:17.000000Z",
     *      "updated_at": "2023-10-26T10:26:17.000000Z",
     *      "important": 1,
     *      "source": "internal",
     *      "default_priority_id": null,
     *      "tasks_relations": [
     *        {
     *          "parent_id": 5,
     *          "child_id": 1
     *        }
     *      ],
     *      "tasks": [],
     *      "phases": []
     *
     * }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    /**
     * @param GanttDataRequest $request
     * @return JsonResponse
     * @throws Exception
     * @throws Throwable
     */
    public function ganttData(GanttDataRequest $request): JsonResponse
    {

        Filter::listen(Filter::getQueryFilterName(), static fn(Builder $query) => $query->with([
            'tasks' => fn(HasMany $queue) => $queue
                ->orderBy('start_date')
                ->select([
                    'id',
                    'task_name',
                    'priority_id',
                    'status_id',
                    'estimate',
                    'start_date',
                    'due_date',
                    'project_phase_id',
                    'project_id'
                ])->with(['status', 'priority'])
                ->withSum(['workers as total_spent_time'], 'duration')
                ->withSum(['workers as total_offset'], 'offset')
                ->withCasts(['start_date' => 'string', 'due_date' => 'string'])
                ->whereNotNull('start_date')->whereNotNull('due_date'),
            'phases' => fn(HasMany $queue) => $queue
                ->select(['id', 'name', 'project_id'])
                ->withMin([
                    'tasks as start_date' => fn(AdjacencyListBuilder $q) => $q
                        ->whereNotNull('start_date')
                        ->whereNotNull('due_date')
                ], 'start_date')
                ->withMax([
                    'tasks as due_date' => fn(AdjacencyListBuilder $q) => $q
                        ->whereNotNull('start_date')
                        ->whereNotNull('due_date')
                ], 'due_date'),
        ]));

        Filter::listen(Filter::getActionFilterName(), static function (Project $item) {
            $item->append('tasks_relations');
            return $item;
        });


        return $this->_show($request);
    }

    /**
     * @api             {get} /projects/phases Project Phases
     * @apiDescription  Получение фаз проекта с количеством задач в каждой фазе
     *
     * @apiVersion      4.0.0
     * @apiName         GetProjectPhases
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer} id  ID проекта
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1
     *  }
     *
     * @apiSuccess {Integer}  id                    ID проекта
     * @apiSuccess {Integer}  company_id            ID компании
     * @apiSuccess {String}   name                  Название проекта
     * @apiSuccess {String}   description           Описание проекта
     * @apiSuccess {String}   deleted_at            Дата удаления проекта или null
     * @apiSuccess {String}   created_at            Дата создания проекта
     * @apiSuccess {String}   updated_at            Дата обновления проекта
     * @apiSuccess {Boolean}  important             Важность проекта
     * @apiSuccess {String}   source                Источник проекта
     * @apiSuccess {Integer}  default_priority_id   ID приоритета по умолчанию или null
     * @apiSuccess {Object[]} phases                Список фаз проекта
     * @apiSuccess {Integer}  phases.id             ID фазы
     * @apiSuccess {String}   phases.name           Название фазы
     * @apiSuccess {String}   phases.description    Описание фазы
     * @apiSuccess {Integer}  phases.tasks_count    Количество задач в фазе
     * @apiSuccess {String}   phases.created_at     Дата создания фазы
     * @apiSuccess {String}   phases.updated_at     Дата обновления фазы
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 1,
     *      "company_id": 1,
     *      "name": "Dolores voluptates.",
     *      "description": "Deleniti maxime fugit nesciunt. Ut maiores deleniti tempora vel. Nisi aut doloremque accusantium tempore aut.",
     *      "deleted_at": null,
     *      "created_at": "2023-10-26T10:26:17.000000Z",
     *      "updated_at": "2023-10-26T10:26:17.000000Z",
     *      "important": 1,
     *      "source": "internal",
     *      "default_priority_id": null,
     *      "phases": []
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    /**
     * @param PhasesRequest $request
     * @return JsonResponse
     * @throws Exception
     * @throws Throwable
     */
    public function phases(PhasesRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getQueryFilterName(),
            static fn(Builder $query) => $query
                ->with([
                    'phases'=> fn(HasMany $q) => $q->withCount('tasks')
            ])
        );

        return $this->_show($request);
    }

    /**
     * @throws Throwable
     */
    public function show(ShowProjectRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getQueryFilterName(),
            static fn(Builder $query) => $query
                ->with([
                    'phases'=> fn(HasMany $q) => $q->withCount('tasks')
                ])
        );
        return $this->_show($request);
    }

    /**
     * @throws Throwable
     * @api             {post} /projects/create Create Project
     * @apiDescription  Creates a new project
     *
     * @apiVersion      4.0.0
     * @apiName         CreateProject
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Boolean}  important             Project importance
     * @apiParam {Integer}  screenshots_state     State of the screenshots
     * @apiParam {String}   name                  Project name
     * @apiParam {String}   description           Project description
     * @apiParam {Integer}  default_priority_id   Default priority ID
     * @apiParam {Object[]} statuses              Project statuses
     * @apiParam {Integer}  statuses.id           Status ID
     * @apiParam {String}   statuses.color        Status color
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "important": true,
     *    "screenshots_state": 1,
     *    "name": "test",
     *    "description": "test",
     *    "default_priority_id": 2,
     *    "statuses": [
     *      {
     *        "id": 2,
     *        "color": null
     *      }
     *    ]
     *  }
     *
     * @apiSuccess {String}   name                Project name
     * @apiSuccess {String}   description         Project description
     * @apiSuccess {Boolean}  important           Project importance
     * @apiSuccess {Integer}  default_priority_id Default priority ID
     * @apiSuccess {Integer}  screenshots_state   State of the screenshots
     * @apiSuccess {String}   created_at          Creation timestamp
     * @apiSuccess {String}   updated_at          Update timestamp
     * @apiSuccess {Object[]} statuses            Project statuses
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "name": "test",
     *      "description": "test",
     *      "important": 1,
     *      "default_priority_id": 2,
     *      "screenshots_state": 1,
     *      "updated_at": "2024-08-06T12:28:07.000000Z",
     *      "created_at": "2024-08-06T12:28:07.000000Z",
     *      "id": 161,
     *      "statuses": []
     *    }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    public function create(CreateProjectRequest $request): JsonResponse
    {
        CatEvent::listen(Filter::getAfterActionEventName(), static function (Project $project, $requestData) use ($request) {
            if ($request->has('statuses')) {
                $statuses = [];
                foreach ($request->get('statuses') as $status) {
                    $statuses[$status['id']] = ['color' => $status['color']];
                }

                $project->statuses()->sync($statuses);
            }

            if (isset($requestData['phases'])) {
                $project->phases()->createMany($requestData['phases']);
            }
        });

        Filter::listen(Filter::getActionFilterName(), static fn($data) => $data->load('statuses'));

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
     * @throws Throwable
     * @api             {post} /projects/edit Edit
     * @apiDescription  Edit Project
     *
     * @apiVersion      4.0.0
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
     * @apiSuccess {Integer}  id          Project ID
     * @apiSuccess {Integer}  company_id  Company ID
     * @apiSuccess {String}   name        Project name
     * @apiSuccess {String}   description Project description
     * @apiSuccess {String}   deleted_at  Deletion timestamp
     * @apiSuccess {String}   created_at  Creation timestamp
     * @apiSuccess {String}   updated_at  Update timestamp
     * @apiSuccess {Boolean}  important   Project importance
     * @apiSuccess {String}   source      Project source
     * @apiSuccess {Integer}  default_priority_id Default priority ID
     * @apiSuccess {Integer}  screenshots_state State of the screenshots
     * @apiSuccess {Object[]} statuses    Project statuses
     *
     * @apiSuccessExample {json} Response Example
     *  {
     *      "id": 1,
     *      "company_id": 1,
     *      "name": "test",
     *      "description": "test",
     *      "deleted_at": null,
     *      "created_at": "2023-10-26T10:26:17.000000Z",
     *      "updated_at": "2024-08-07T16:47:01.000000Z",
     *      "important": 1,
     *      "source": "internal",
     *      "default_priority_id": null,
     *      "screenshots_state": 1,
     *      "statuses": []
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */
    public function edit(EditProjectRequest $request): JsonResponse
    {
        CatEvent::listen(Filter::getAfterActionEventName(), static function (Project $project, $requestData) use ($request) {
            if ($request->has('statuses')) {
                $statuses = [];
                foreach ($request->get('statuses') as $status) {
                    $statuses[$status['id']] = ['color' => $status['color']];
                }

                $project->statuses()->sync($statuses);
            }

            if (isset($requestData['phases'])) {
                $phases = collect($requestData['phases']);
                $project->phases()
                    ->whereNotIn('id', $phases->pluck('id')->filter())
                    ->delete();
                $project->phases()->upsert(
                    $phases->filter(fn (array $val) => isset($val['id']))->toArray(),
                    ['id'],
                    ['name']
                );
                $project->phases()->createMany($phases->filter(fn (array $val) => !isset($val['id'])));
            }
        });

        Filter::listen(Filter::getActionFilterName(), static fn($data) => $data->load('statuses'));

        return $this->_edit($request);
    }

    /**
     * @throws Throwable
     * @api             {post} /projects/remove Destroy
     * @apiDescription  Destroy Project
     *
     * @apiVersion      4.0.0
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
     *  HTTP/1.1 204 No Content
     *  {
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     * @apiUse          ItemNotFoundError
     */
    public function destroy(DestroyProjectRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @throws Exception
     * @api             {get,post} /projects/count Count
     * @apiDescription  Count Projects
     *
     * @apiVersion      4.0.0
     * @apiName         Count
     * @apiGroup        Project
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_count
     * @apiPermission   projects_full_access
     *
     * @apiSuccess {Integer}   total    Amount of projects that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "total": 159
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function count(ListProjectRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
