<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\TaskActivity\ShowTaskActivityRequest;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Filter;
use App\Helpers\QueryHelper;
use Event;

class TaskActivityController extends ItemController
{
    private function getQueryBuilder(array $filter = [], string $model): Builder
    {
        $model = new $model;

        $query = new Builder($model::getQuery(), $model);
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

    private function getCollectionFromModel(array $requestData, string $model): Collection
    {

        $itemsQuery = $this->getQueryBuilder($requestData, $model);

        $items = $itemsQuery->get();

        Filter::process(
            Filter::getActionFilterName(),
            $items,
        );

        return $items;
    }

    private function getCollection(array $requestData): array
    {
        $result = false;

        if ($requestData['type'] === "all") {
            $result = array_merge(
                $this->getCollectionFromModel($requestData, TaskComment::class)->toArray(),
                $this->getCollectionFromModel($requestData, TaskHistory::class)->toArray()
            );
        } else if ($requestData['type'] === "history") {
            $result = $this->getCollectionFromModel($requestData, TaskHistory::class)->toArray();
        } else if ($requestData['type'] === "comments") {
            $result = $this->getCollectionFromModel($requestData, TaskComment::class)->toArray();
        }

        return $result;
    }

    private function sortCollection(array &$collection, array $sort): void
    {
        usort($collection, function ($a, $b) use ($sort) {
            if ($sort[1] === "desc")
                return strtotime($a[$sort[0]]) < strtotime($b[$sort[0]]);
            else
                return strtotime($a[$sort[0]]) > strtotime($b[$sort[0]]);
        });
    }

    private function getPaginateCollection(array $collection, int $currentPage, $perPage = 10): LengthAwarePaginator
    {
        $collection = new Collection($collection);

        $currentPageSearchResults = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        return new LengthAwarePaginator($currentPageSearchResults, count($collection), $perPage);
    }

    /**
     * @param ListTaskRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ShowTaskActivityRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        Event::dispatch(Filter::getBeforeActionEventName(), $requestData);

        $items = $this->getCollection($requestData);
        $this->sortCollection($items, $requestData['orderBy']);
        $items = $this->getPaginateCollection($items, $requestData['page']);

        Event::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);
        return responder()->success($items)->respond();
    }
}
