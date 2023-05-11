<?php

namespace App\Http\Controllers\Api;

use App\Helpers\QueryHelper;
use App\Http\Requests\Project\EditProjectRequest;
use App\Http\Requests\ProjectGroup\CreateProjectGroupRequest;
use App\Http\Requests\ProjectGroup\DestroyProjectGroupRequest;
use App\Http\Requests\ProjectGroup\EditProjectGroupRequest;
use App\Http\Requests\ProjectGroup\ListProjectGroupRequest;
use App\Http\Requests\ProjectGroup\ShowProjectGroupRequest;
use App\Models\ProjectGroup;
use Event;
use Exception;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Kalnoy\Nestedset\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ProjectGroupController extends ItemController
{
    protected const MODEL = ProjectGroup::class;

    /**
     * Display a listing of the resource.
     *
     * @param ListProjectGroupRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ListProjectGroupRequest $request): JsonResponse
    {
        // TODO:
        //  [] remove/setAnother amount in ->paginate(2)
        
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $itemsQuery = $this->getQuery($requestData);

        Event::dispatch(Filter::getBeforeActionEventName(), $requestData);

        $itemsQuery->withDepth()->withCount('projects')->defaultOrder();

        $items = $request->header('X-Paginate', true) !== 'false' ? $itemsQuery->paginate($request->input('limit', null)) : $itemsQuery->get();

        Filter::process(
            Filter::getActionFilterName(),
            $items,
        );

        Event::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);

        return responder()->success($items)->respond();
    }

    /**
     * @throws Exception
     */
    protected function getQuery(array $filter = []): QueryBuilder
    {
        $model = static::MODEL;
        $model = new $model;

        $query = new QueryBuilder($model::getQuery());
        $query->setModel($model);

        $modelScopes = $model->getGlobalScopes();

        foreach ($modelScopes as $key => $value) {
            $query->withGlobalScope($key, $value);
        }

        foreach (Filter::process(Filter::getQueryAdditionalRelationsFilterName(), []) as $with) {
            $query->with($with);
        }
        
        QueryHelper::apply($query, $model, $filter);

        return Filter::process(
            Filter::getQueryFilterName(),
            $query
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param CreateProjectGroupRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function create(CreateProjectGroupRequest $request): JsonResponse
    {
        if ($parent_id = $request->safe(['parent_id'])['parent_id'] ?? null) {
            Event::listen(
                Filter::getAfterActionEventName(),
                static fn(ProjectGroup $group) => $group->parent()->associate($parent_id)->save(),
            );
        }

        return $this->_create($request);
    }

    /**
     * Display the specified resource.
     *
     * @param ShowProjectGroupRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function show(ShowProjectGroupRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param EditProjectRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function edit(EditProjectGroupRequest $request): JsonResponse
    {
        if ($parent_id = $request->safe(['parent_id'])['parent_id'] ?? null) {
            Event::listen(
                Filter::getAfterActionEventName(),
                static fn(ProjectGroup $group) => $group->parent()->associate($parent_id)->save(),
            );
        }
        if ($parent_id == null) {
            Event::listen(
                Filter::getAfterActionEventName(),
                static fn(ProjectGroup $group) => $group->saveAsRoot(),
            );
        }

        return $this->_edit($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyProjectGroupRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function destroy(DestroyProjectGroupRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @throws Exception
     */
    public function count(ListProjectGroupRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
