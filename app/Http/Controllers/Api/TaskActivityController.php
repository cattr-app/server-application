<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActivityType;
use App\Http\Requests\TaskActivity\ShowTaskActivityRequest;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use CatEvent;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Filter;
use App\Helpers\QueryHelper;
use Event;

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
        $query->orderBy('id', $filter['orderBy'][1]);

        return Filter::process(
            Filter::getQueryFilterName(),
            $query
        );
    }

    private function getCollectionFromModel(array $requestData, string $model): LengthAwarePaginator
    {

        $itemsQuery = $this->getQueryBuilder($requestData, $model);

        $items = $itemsQuery->paginate(2);

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
        } elseif ($requestData['type'] === "history") {
            $result = $this->getCollectionFromModel($requestData, TaskHistory::class)->toArray();
        } elseif ($requestData['type'] === "comments") {
            $result = $this->getCollectionFromModel($requestData, TaskComment::class)->toArray();
        }

        return $result;
    }

    private function sortCollection(array &$collection, array $sort): void
    {
        usort($collection, function ($a, $b) use ($sort) {
            if ($sort[1] === "desc") {
                return strtotime($a[$sort[0]]) < strtotime($b[$sort[0]]);
            } else {
                return strtotime($a[$sort[0]]) > strtotime($b[$sort[0]]);
            }
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
        $requestedActivity = ActivityType::from($requestData['type']);
        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestData);

        // TODO:
//          [] check if it's ok to place LengthAwarePaginator into LengthAwarePaginator
        $items = [];
        $total = 0;
        $perPage = 0;
        if ($requestedActivity === ActivityType::ALL) {
            $taskComments = $this->getCollectionFromModel($requestData, TaskComment::class);
            $taskHistory = $this->getCollectionFromModel($requestData, TaskHistory::class);
            $total = $taskComments->total() + $taskHistory->total();
            $perPage = $taskComments->perPage() + $taskHistory->perPage();
            $items = array_merge($taskComments->items(), $taskHistory->items());
        } elseif ($requestedActivity === ActivityType::HISTORY) {
            $taskHistory = $this->getCollectionFromModel($requestData, TaskHistory::class);
            $total = $taskHistory->total();
            $perPage = $taskHistory->perPage();
            $items = $taskHistory->items();
        } elseif ($requestedActivity === ACTIVITYType::COMMENTS) {
            $taskComments = $this->getCollectionFromModel($requestData, TaskComment::class);
            $total = $taskComments->total();
            $perPage = $taskComments->perPage();
            $items = $taskComments->items();
        }
//        dump([$total, $perPage, $items, $requestData]);

//        $this->sortCollection($result, $requestData['orderBy']);
//        $items = $this->getPaginateCollection($result, $requestData['page']);

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);

        return responder()->success(new LengthAwarePaginator($items, $total, $perPage))->respond();








        $type = $request->input('type', 'all');
        $perPage = $request->input('per_page', 2); // Items per page, default to 15
        $page = $request->input('page', 1); // Current page, default to 1
        $offset = ($page - 1) * $perPage;

    // Base query for combining and sorting by created_at
        $commentsQuery = DB::table('task_comment')
            ->select('id', 'task_id', 'user_id', 'content as content', DB::raw('NULL as field'), DB::raw('NULL as old_value'), DB::raw('NULL as new_value'), 'created_at', DB::raw('"comment" as type'));

        $historiesQuery = DB::table('task_history')
            ->select('id', 'task_id', 'user_id', DB::raw('NULL as content'), 'field', 'old_value', 'new_value', 'created_at', DB::raw('"history" as type'));

        if ($type == 'comments') {
            $query = $commentsQuery;
        } elseif ($type == 'history') {
            $query = $historiesQuery;
        } else {
            $query = $commentsQuery->unionAll($historiesQuery);
        }

    // Fetch total count for pagination
        $total = DB::table(DB::raw("({$query->toSql()}) as combined"))->mergeBindings($query)->count();

    // Fetch the paginated result
        $results = DB::table(DB::raw("({$query->toSql()}) as combined"))
                ->mergeBindings($query)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

    // Create LengthAwarePaginator instance
        $paginated = new LengthAwarePaginator($results, $total, $perPage, $page, [
        'path' => LengthAwarePaginator::resolveCurrentPath(),
        'query' => request()->query(),
        ]);

        return responder()->success($paginated->toArray())->respond();




    //    $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

    //    Event::dispatch(Filter::getBeforeActionEventName(), $requestData);
//
  //      $items = $this->getCollection($requestData);
    //    $this->sortCollection($items, $requestData['orderBy']);
      //  $items = $this->getPaginateCollection($items, $requestData['page']);
//
  //      Event::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);
    //    return responder()->success($items)->respond();
    }
}
