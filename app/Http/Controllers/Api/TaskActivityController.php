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
    private const PER_PAGE = 30;

    private static function getSortDirection(array $requestData): string
    {
        return SortDirection::tryFrom(strtolower($requestData['orderBy'][1] ?? ''))?->value ?? 'asc';
    }

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
        // The request is authorized for this task only, whatever the client-supplied `where` says
        $query->where($model->getTable() . '.task_id', $filter['task_id']);
        $sortDirection = self::getSortDirection($filter);
        $query->orderBy('id', $sortDirection);

        return Filter::process(
            Filter::getQueryFilterName(),
            $query
        );
    }

    private function getActivityQuery(array $requestData, string $model): Builder
    {
        if ($model === TaskComment::class) {
            $requestData['with'][] = 'attachmentsRelation';
            $requestData['with'][] = 'attachmentsRelation.user:id,full_name';
        }

        return $this->getQueryBuilder($requestData, $model);
    }

    private function getCollectionFromModel(array $requestData, string $model): LengthAwarePaginator
    {
        $items = $this->getActivityQuery($requestData, $model)->paginate(self::PER_PAGE);

        Filter::process(
            Filter::getActionFilterName(),
            $items,
        );

        return $items;
    }

    /**
     * Merges comments and history into a single stream and paginates that stream,
     * so the items and the pagination metadata describe the same sequence.
     */
    private function getMergedCollection(array $requestData): LengthAwarePaginator
    {
        $page = max(1, (int)$requestData['page']);
        $sortDirection = self::getSortDirection($requestData);
        $merged = collect();
        $total = 0;

        $queries = [];
        foreach ([TaskComment::class, TaskHistory::class] as $model) {
            // Every source must be ordered like the merged stream, otherwise a prefix of it is not a prefix of the page
            $queries[] = $query = $this->getActivityQuery($requestData, $model)
                ->reorder($model::make()->getTable() . '.created_at', $sortDirection)
                ->orderBy('id', $sortDirection);
            $total += $query->toBase()->getCountForPagination();
        }

        // The page can start anywhere in either source, so each one has to supply every row up to the page end
        if (($page - 1) * self::PER_PAGE < $total) {
            foreach ($queries as $query) {
                $merged = $merged->concat($query->forPage(1, $page * self::PER_PAGE)->get());
            }
        }

        $direction = $sortDirection === 'asc' ? 1 : -1;
        $items = $merged
            ->sort(fn ($a, $b) => $direction * (
                strtotime($a['created_at']) <=> strtotime($b['created_at'])
                ?: ($a instanceof TaskComment ? 0 : 1) <=> ($b instanceof TaskComment ? 0 : 1)
                ?: $a['id'] <=> $b['id']
            ))
            ->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)
            ->values();

        $paginator = new LengthAwarePaginator($items, $total, self::PER_PAGE, $page);

        Filter::process(Filter::getActionFilterName(), $paginator);

        return $paginator;
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

        $activity = match ($requestedActivity) {
            ActivityType::ALL => $this->getMergedCollection($requestData),
            ActivityType::HISTORY => $this->getCollectionFromModel($requestData, TaskHistory::class),
            ActivityType::COMMENTS => $this->getCollectionFromModel($requestData, TaskComment::class),
        };
        $items = $activity->items();

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);

        return responder()->success($activity)->respond();
    }
}
