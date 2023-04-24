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

        $items = $itemsQuery->withDepth()->withCount('projects')->defaultOrder()->paginate($request->limit ? $request->limit : 2);

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
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        Event::dispatch(Filter::getBeforeActionEventName(), [$requestData]);
        
        /** @var Model $cls */
        $cls = static::MODEL;

        $parent_id = isset($requestData['parent_id']) ? $requestData['parent_id'] : null;

        $item = Filter::process(
            Filter::getActionFilterName(),
            $cls::create($requestData, ProjectGroup::find($parent_id)),
        );

        Event::dispatch(Filter::getAfterActionEventName(), [$item, $requestData]);

        return responder()->success($item)->respond();
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
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        throw_unless(is_int($request->get('id')), ValidationException::withMessages(['Invalid id']));

        $itemsQuery = $this->getQuery();

        /** @var Model $item */
        $item = $itemsQuery->get()->collect()->firstWhere('id', $request->get('id'));

        if (!$item) {
            /** @var Model $cls */
            $cls = static::MODEL;
            throw_if($cls::find($request->get('id'))?->count(), new AccessDeniedHttpException());

            throw new NotFoundHttpException;
        }

        Event::dispatch(Filter::getBeforeActionEventName(), [$item, $requestData]);

        $parent = isset($requestData['parent_id']) ? ProjectGroup::find($requestData['parent_id']) : null;

        $item = Filter::process(Filter::getActionFilterName(), $item->fill($requestData)->parent()->associate($parent));

        $item->save();

        Event::dispatch(Filter::getAfterActionEventName(), [$item, $requestData]);

        return responder()->success($item)->respond();
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
