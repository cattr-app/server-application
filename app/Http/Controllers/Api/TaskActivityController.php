<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActivityType;
use App\Enums\SortDirection;
use App\Http\Requests\TaskActivity\ShowTaskActivityRequest;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use CatEvent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Filter;
use App\Helpers\QueryHelper;

class TaskActivityController extends ItemController
{
    private function getQueryBuilder(array $filter, string $model): Builder
    {
        $model = new $model;

        $query = new Builder($model::getQuery());
        $query->setModel($model);

        $modelScopes = $model->getGlobalScopes();

        foreach ($modelScopes as $key => $value) {
            $query->withGlobalScope($key, $value);
        }

        foreach (Filter::process(Filter::getQueryAdditionalRelationsFilterName(), []) as $with) {
            $query->with($with);
        }

        QueryHelper::apply($query, $model, $filter);
        $sortDirection = SortDirection::tryFrom($filter['orderBy'][1])?->value ?? 'asc';
        $query->orderBy('id', $sortDirection);

        return Filter::process(
            Filter::getQueryFilterName(),
            $query
        );
    }

    private function getCollectionFromModel(array $requestData, string $model): LengthAwarePaginator
    {
        if ($model === TaskComment::class) {
            $requestData['with'][] = 'attachmentsRelation';
            $requestData['with'][] = 'attachmentsRelation.user:id,full_name';
        }

        $itemsQuery = $this->getQueryBuilder($requestData, $model);

        $items = $itemsQuery->paginate(30);

        Filter::process(
            Filter::getActionFilterName(),
            $items,
        );

        return $items;
    }

    /**
     * @param ShowTaskActivityRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ShowTaskActivityRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());
        $requestedActivity = ActivityType::from($requestData['type']);
        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestData);

        $items = [];
        $total = 0;
        $perPage = 0;
        if ($requestedActivity === ActivityType::ALL) {
            $taskComments = $this->getCollectionFromModel($requestData, TaskComment::class);
            $taskHistory = $this->getCollectionFromModel($requestData, TaskHistory::class);
            $total = $taskComments->total() + $taskHistory->total();
            $perPage = $taskComments->perPage() + $taskHistory->perPage();

            $sortDirection = SortDirection::tryFrom($requestData['orderBy'][1])?->value ?? 'asc';
            $items = collect(array_merge($taskComments->items(), $taskHistory->items()))->sortBy([
                fn ($a, $b) => $sortDirection === 'asc'
                    ? strtotime($a['created_at']) <=> strtotime($b['created_at'])
                    : strtotime($b['created_at']) <=> strtotime($a['created_at']),
            ], SORT_REGULAR, $sortDirection === 'desc');
        } elseif ($requestedActivity === ActivityType::HISTORY) {
            $taskHistory = $this->getCollectionFromModel($requestData, TaskHistory::class);
            $total = $taskHistory->total();
            $perPage = $taskHistory->perPage();
            $items = $taskHistory->items();
        } elseif ($requestedActivity === ActivityType::COMMENTS) {
            $taskComments = $this->getCollectionFromModel($requestData, TaskComment::class);
            $total = $taskComments->total();
            $perPage = $taskComments->perPage();
            $items = $taskComments->items();
        }

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);

        return responder()->success(new LengthAwarePaginator($items, $total, $perPage))->respond();
    }
}
