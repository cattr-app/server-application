<?php

namespace App\Http\Controllers\Api;

use Filter;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Event;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Validator;

abstract class ItemController extends Controller
{
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
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function _index(Request $request): JsonResponse
    {
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            Filter::getQueryPrepareFilterName(),
            $this->applyQueryFilter(
                $this->getQuery(),
                $request->all() ?: []
            )
        );

        return responder()->success(
            $request->header('X-Paginate', true) !== 'false' ? $itemsQuery->paginate() : $itemsQuery->get()
        )->respond();
    }

    /**
     * Returns event's name with current item's unique part
     * @param string $eventName
     * @return String
     */
    protected function getEventUniqueName(string $eventName): string
    {
        return "$eventName.{$this->getEventUniqueNamePart()}";
    }

    /**
     * Returns unique part of event name for current item
     */
    abstract public function getEventUniqueNamePart(): string;

    /**
     * @param Builder $query
     * @param array $filter
     * @return Builder
     * @throws Exception
     */
    protected function applyQueryFilter(Builder $query, array $filter = []): Builder
    {
        $cls = static::getItemClass();
        $model = new $cls();
        $helper = new QueryHelper();

        $helper->apply($query, $model, $filter);

        return Filter::process(
            Filter::getQueryFiltrationFilterName(),
            $query
        );
    }

    /**
     * Returns current item's class name
     */
    abstract public function getItemClass(): string;

    protected function getQuery(bool $withRelations = true, bool $withSoftDeleted = false): Builder
    {
        /** @var Model $cls */
        $cls = static::getItemClass();
        $model = new $cls;

        $query = new Builder($cls::getQuery());
        $query->setModel($model);

        $modelScopes = $model->getGlobalScopes();

        foreach ($modelScopes as $key => $value) {
            $query->withGlobalScope($key, $value);
        }

        if ($withSoftDeleted) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        if ($withRelations) {
            foreach ($this->getQueryWith() as $with) {
                $query->with($with);
            }
        }

        return Filter::process(
            Filter::getQueryGetFilterName(),
            $query
        );
    }

    public function getQueryWith(): array
    {
        return [];
    }

    /**
     * Display count of the resource
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function _count(Request $request): JsonResponse
    {
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            filter::getQueryPrepareFilterName(),
            $this->applyQueryFilter(
                $this->getQuery(),
                $request->all() ?: []
            )
        );

        return responder()->success(['total' => $itemsQuery->count()])->respond();
    }

    /**
     * @throws Throwable
     */
    public function _create(FormRequest $request): JsonResponse
    {
        $requestData = $request->validated();

        /** @var Model $cls */
        $cls = $this->getItemClass();

        Event::dispatch($this->getEventUniqueName('item.create.before'), $requestData);

        $item = Filter::process(
            $this->getEventUniqueName('item.create'),
            $cls::create($this->filterRequestData($requestData))
        );

        Event::dispatch($this->getEventUniqueName('item.create.after'), [$item, $requestData]);

        return responder()->success($item)->respond();
    }

    /**
     * Returns validation rules for current item
     */
    abstract public function getValidationRules(): array;

    /**
     * Opportunity to filtering request data
     *
     * Override this in child class for filtering
     */
    protected function filterRequestData(array $requestData): array
    {
        return $requestData;
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @throws Throwable
     */
    public function _show(Request $request): JsonResponse
    {
        $itemId = (int)$request->input('id');

        throw_unless($itemId, ValidationException::withMessages(['Invalid id']));

        $filters = [
            'id' => $itemId
        ];
        $request->get('with') ? $filters['with'] = $request->get('with') : false;
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            Filter::getQueryPrepareFilterName(),
            $this->applyQueryFilter(
                $this->getQuery(),
                $filters ?: []
            )
        );

        $item = $itemsQuery->first();

        throw_unless($item, new NotFoundHttpException);

        return responder()->success($item)->respond();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @throws Throwable
     */
    public function _edit(Request $request): JsonResponse
    {
        $requestData = Filter::process(
            $this->getEventUniqueName('request.item.edit'),
            $request->all()
        );

        $validationRules = $this->getValidationRules();
        $validationRules['id'] = ['required'];

        $validator = Validator::make(
            $requestData,
            Filter::process(
                $this->getEventUniqueName('validation.item.edit'),
                $validationRules
            )
        );

        throw_if($validator->fails(), ValidationException::withMessages($validator->messages()->all()));

        throw_unless(is_int($request->get('id')), ValidationException::withMessages(['Invalid id']));

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            filter::getQueryPrepareFilterName(),
            $this->applyQueryFilter(
                $this->getQuery()
            )
        );

        /** @var Model $item */
        $item = collect($itemsQuery->get())->first(static function ($val, $key) use ($request) {
            return $val['id'] === $request->get('id');
        });

        if (!$item) {
            /** @var Model $cls */
            $cls = $this->getItemClass();
            throw_if($cls::find($request->get('id'))?->count(), new AccessDeniedHttpException);

            throw new NotFoundHttpException;
        }

        Event::dispatch($this->getEventUniqueName('item.edit.before'), [$item, $requestData]);

        $item->fill($this->filterRequestData($requestData));
        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);
        $item->save();

        Event::dispatch($this->getEventUniqueName('item.edit.after'), [$item, $requestData]);

        return responder()->success($item)->respond();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function _destroy(Request $request): JsonResponse
    {
        $itemId = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->get('id'));

        throw_unless(is_int($itemId), ValidationException::withMessages(['Invalid id']));

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            filter::getQueryPrepareFilterName(),
            $this->applyQueryFilter(
                $this->getQuery(),
                ['id' => $itemId]
            )
        );

        /** @var Model $item */
        $item = $itemsQuery->first();
        if (!$item) {
            /** @var Model $cls */
            $cls = $this->getItemClass();
            throw_if($cls::find($request->get('id'))?->count(), new AccessDeniedHttpException);

            throw new NotFoundHttpException;
        }

        Event::dispatch($this->getEventUniqueName('item.delete.before'), $item);

        $item = Filter::process($this->getEventUniqueName('item.remove'), $item);
        $item->delete();

        Event::dispatch($this->getEventUniqueName('item.delete.after'), $item);

        return responder()->success()->respond(204);
    }
}
