<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CattrFormRequest;
use Filter;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use CatEvent;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

abstract class ItemController extends Controller
{
    protected const MODEL = Model::class;

    /**
     * @apiDefine ItemNotFoundError
     * @apiErrorExample {json} No such item
     *  HTTP/1.1 404 Not Found
     *  {
     *    "message": "Item not found",
     *    "error_type": "query.item_not_found"
     *  }
     *
     * @apiVersion 1.0.0
     */

    /**
     * @apiDefine ValidationError
     * @apiErrorExample {json} Validation error
     *  HTTP/1.1 400 Bad Request
     *  {
     *    "message": "Validation error",
     *    "error_type": "validation",
     *    "info": "Invalid id"
     *  }
     *
     * @apiError (Error 400) {String}  info  Validation errors
     *
     * @apiVersion 1.0.0
     */

    /**
     * @throws Exception
     */
    public function _index(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $itemsQuery = $this->getQuery($requestData);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestData);

        $items = $request->header('X-Paginate', true) !== 'false' ? $itemsQuery->paginate() : $itemsQuery->get();

        Filter::process(
            Filter::getActionFilterName(),
            $items,
        );

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);

        return responder()->success($items)->respond();
    }

    /**
     * @throws Exception
     */
    protected function getQuery(array $filter = []): Builder
    {
        $model = static::MODEL;
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

        return Filter::process(
            Filter::getQueryFilterName(),
            $query
        );
    }

    /**
     * @throws Throwable
     */
    public function _create(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$requestData]);

        /** @var Model $cls */
        $cls = static::MODEL;

        $item = Filter::process(
            Filter::getActionFilterName(),
            $cls::create($requestData),
        );

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$item, $requestData]);

        return responder()->success($item)->respond();
    }

    /**
     * @throws Throwable
     */
    public function _edit(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        throw_unless(is_int($request->get('id')), ValidationException::withMessages(['Invalid id']));

        $itemsQuery = $this->getQuery();

        /** @var Model $item */
        $item = $itemsQuery->get()->collect()->firstWhere('id', $request->get('id'));

        if (!$item) {
            /** @var Model $cls */
            $cls = static::MODEL;
            throw_if($cls::find($request->get('id'))?->count(), new AccessDeniedHttpException);

            throw new NotFoundHttpException;
        }

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$item, $requestData]);

        $item = Filter::process(Filter::getActionFilterName(), $item->fill($requestData));
        $item->save();

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$item, $requestData]);

        return responder()->success($item)->respond();
    }

    /**
     * @throws Throwable
     */
    public function _destroy(CattrFormRequest $request): JsonResponse
    {
        $requestId = Filter::process(Filter::getRequestFilterName(), $request->validated('id'));

        throw_unless(is_int($requestId), ValidationException::withMessages(['Invalid id']));

        $itemsQuery = $this->getQuery(['where' => ['id' => $requestId]]);

        /** @var Model $item */
        $item = $itemsQuery->first();
        if (!$item) {
            /** @var Model $cls */
            $cls = static::MODEL;
            throw_if($cls::find($requestId)?->count(), new AccessDeniedHttpException);

            throw new NotFoundHttpException;
        }

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestId);

        CatEvent::dispatch(
            Filter::getAfterActionEventName(),
            tap(
                Filter::process(Filter::getActionFilterName(), $item),
                static fn($item) => $item->delete(),
            )
        );

        return responder()->success()->respond(204);
    }

    /**
     * @throws Exception
     */
    protected function _count(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestData);

        $itemsQuery = $this->getQuery($requestData);

        $count = Filter::process(Filter::getActionFilterName(), $itemsQuery->count());

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$count, $requestData]);

        clock()->event();

        return responder()->success(['total' => $count])->respond();
    }

    /**
     * @throws Throwable
     */
    protected function _show(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $itemId = (int)$requestData['id'];

        throw_unless($itemId, ValidationException::withMessages(['Invalid id']));

        $filters = [
            'where' => ['id' => $itemId]
        ];

        if (!empty($requestData['with'])) {
            $filters['with'] = $requestData['with'];
        }

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $filters);

        $itemsQuery = $this->getQuery($filters ?: []);

        $item = Filter::process(Filter::getActionFilterName(), $itemsQuery->first());

        throw_unless($item, new NotFoundHttpException);

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$item, $filters]);

        return responder()->success($item)->respond();
    }
}
