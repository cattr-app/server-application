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
     * List
     *
     * Get list of Projects
     *
     * Required permission (any of): `projects_list`, `projects_full_access`
     *
     * @param ListProjectRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ListProjectRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * Gantt Data
     *
     * Получение данных для диаграммы Ганта по проекту
     *
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
     * Project Phases
     *
     * Retrieve project phases along with the number of tasks in each phase.
     *
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
     * Project Show
     *
     * Retrieve project show along with the number of tasks in each phase.
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
     * Create Project
     *
     * Creates a new project
     */
    public function create(CreateProjectRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($requestData) {
            if (isset($requestData['group']) && is_array($requestData['group'])) {
                $requestData['group'] = $requestData['group']['id'];
            }

            return $requestData;
        });

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
     * Edit
     *
     * Edit Project
     *
     * Required permission (any of): `projects_edit`, `projects_full_access`
     */
    public function edit(EditProjectRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($requestData) {
            if (isset($requestData['group']) && is_array($requestData['group'])) {
                $requestData['group'] = $requestData['group']['id'];
            }

            return $requestData;
        });

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
     * Destroy
     *
     * Destroy Project
     *
     * Required permission (any of): `projects_remove`, `projects_full_access`
     */
    public function destroy(DestroyProjectRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * Count
     *
     * Count Projects
     *
     * Required permission (any of): `projects_count`, `projects_full_access`
     */
    public function count(ListProjectRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
