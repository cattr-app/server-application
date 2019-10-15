<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Schema;
use Validator;
use Illuminate\Database\Eloquent\Model;
use App\Models\DateTrait;
use Event;


/**
 * Class ItemController
 *
 * @package App\Http\Controllers\Api\v1
 */
abstract class ItemController extends Controller
{
    /**
     * Returns current item's class name
     *
     * @return string|Model
     */
    abstract public function getItemClass(): string;

    /**
     * Returns validation rules for current item
     *
     * @return array
     */
    abstract public function getValidationRules(): array;

    /**
     * Returns unique part of event name for current item
     *
     * @return string
     */
    abstract public function getEventUniqueNamePart(): string;

    /**
     * @return string[]
     */
    public function getQueryWith(): array
    {
        return [];
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $request->all() ?: []
            )
        );

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $itemsQuery->get()
            )
        );
    }

    /**
     * Display count of the resource
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $request->all() ?: []
            )
        );

        return response()->json([
            'total' => Filter::process(
                $this->getEventUniqueName('answer.success.item.list.count.query.prepare'),
                $itemsQuery->get()
            )->count()
        ]);
    }

    /**
     * @apiDefine DefaultCreateErrorResponse
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     */

    /**
     * @apiDefine DefaultBulkCreateErrorResponse
     * @apiError (Error 200) {Object[]}  messages               Errors
     * @apiError (Error 200) {Object}    messages.object        Error object
     * @apiError (Error 200) {String}    messages.object.error  Name of error
     * @apiError (Error 200) {String}    messages.object.reason Reason of error
     * @apiError (Error 200) {Integer}   messages.object.code   Code of error
     *
     * @apiError (Error 400) {Object[]} messages                Errors
     * @apiError (Error 400) {Object}   messages.object         Error
     * @apiError (Error 400) {String}   messages.object.error   Name of error
     * @apiError (Error 400) {String}   messages.object.reason  Reason of error
     */

    /**
     * Create item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
        }

        $cls = $this->getItemClass();

        Event::dispatch($this->getEventUniqueName('item.create.before'), $requestData);

        $item = Filter::process(
            $this->getEventUniqueName('item.create'),
            $cls::create($this->filterRequestData($requestData))
        );

        Event::dispatch($this->getEventUniqueName('item.create.after'), [$item, $requestData]);

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @apiDefine DefaultShowErrorResponse
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     */

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(Request $request): JsonResponse
    {
        $itemId = is_int($request->get('id')) ? $request->get('id') : false;

        if (!$itemId) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'error' => 'Validation fail',
                    'reason' => 'Id invalid',
                ]),
                400
            );
        }

        $filters = [
            'id' => $itemId
        ];
        $request->get('with') ? $filters['with'] = $request->get('with') : false;
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $filters ?: []
            )
        );

        $item = $itemsQuery->first();

        if (!$item) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'error' => 'Item not found'
                ]),
                404
            );
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.show'), $item)
        );
    }

    /**
     * @apiDefine DefaultEditErrorResponse
     *
     * @apiError (Error 400) {String} error  Error name
     * @apiError (Error 400) {String} reason Reason
     */

    /**
     * @apiDefine DefaultBulkEditErrorResponse
     *
     * Yes, we send errors with 200 HTTP status-code, because 207 use WebDAV
     * and REST API have some architecture problems
     *
     * @apiError (Error 200) {Object[]}  messages               Errors
     * @apiError (Error 200) {Object}    messages.object        Error
     * @apiError (Error 200) {String}    messages.object.error  Error name
     * @apiError (Error 200) {String}    messages.object.reason Reason
     * @apiError (Error 200) {Integer}   messages.object.code   Error Status-Code
     *
     * @apiError (Error 400) {Object[]} messages               Errors
     * @apiError (Error 400) {Object}   messages.object        Error
     * @apiError (Error 400) {String}   messages.object.error  Name of error
     * @apiError (Error 400) {String}   messages.object.reason Reason of error
     */

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws MassAssignmentException
     * @throws ModelNotFoundException
     */
    public function edit(Request $request): JsonResponse
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

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
        }

        if (!is_int($request->get('id'))) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'Invalid id',
                    'reason' => 'Id is not integer',
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery()
            )
        );

        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = collect($itemsQuery->get())->first(function ($val, $key) use ($request) {
            return $val['id'] === $request->get('id');
        });

        if (!$item) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'Model fetch fail',
                    'reason' => 'Model not found',
                ]),
                400
            );
        }

        $item->fill($this->filterRequestData($requestData));
        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);
        $item->save();

        Event::dispatch($this->getEventUniqueName('item.edit.after'), [$item, $requestData]);

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @apiDefine DefaultDestroyRequestExample
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id": 1
     *  }
     */

    /**
     * @apiDefine DefaultDestroyResponse
     * @apiSuccess {String}    message      Message about success remove
     * @apiError   (Error 404) ItemNotFound HTTP/1.1 404 Page Not Found
     */

    /**
     * @apiDefine DefaultBulkDestroyErrorResponse
     *
     * @apiError (Error 200) {Object[]}  messages               Errors
     * @apiError (Error 200) {Object}    messages.object        Error object
     * @apiError (Error 200) {String}    messages.object.error  Name of error
     * @apiError (Error 200) {String}    messages.object.reason Reason of error
     * @apiError (Error 200) {Integer}   messages.object.code   Code of error
     *
     * @apiError (Error 400) {Object[]} messages               Errors
     * @apiError (Error 400) {Object}   messages.object        Error object
     * @apiError (Error 400) {String}   messages.object.error  Name of error
     * @apiError (Error 400) {String}   messages.object.reason Reason of error
     */

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request): JsonResponse
    {
        $itemId = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->get('id'));
        $idInt = is_int($itemId);

        if (!$idInt) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.destroy'), [
                    'error' => 'Validation fail',
                    'reason' => 'Id invalid',
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), ['id' => $itemId]
            )
        );

        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = $itemsQuery->firstOrFail();
        $item->delete();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }

    /**
     * Opportunity to filtering request data
     *
     * Override this in child class for filtering
     * @param array $requestData
     * @return array
     */
    protected function filterRequestData(array $requestData): array
    {
        return $requestData;
    }

    /**
     * Returns event's name with current item's unique part
     *
     * @param $eventName
     * @return string
     */
    protected function getEventUniqueName(string $eventName): String
    {
        return "{$eventName}.{$this->getEventUniqueNamePart()}";
    }

    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        /** @var Model $cls */
        $cls = static::getItemClass();

        $query = new Builder($cls::getQuery());
        $query->setModel(new $cls());

        $softDelete = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($cls));

        if ($softDelete) {
            if (method_exists($cls, 'getTable')) {
                $table = (new $cls())->getTable();
                $query->whereNull("$table.deleted_at");
            } else {
                $query->whereNull('deleted_at');
            }
        }

        if ($withRelations) {
            foreach ($this->getQueryWith() as $with) {
                $query->with($with);
            }
        }

        return Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.get'),
            $query
        );
    }

    /**
     * @param Builder $query
     * @param array $filter
     *
     * @return Builder
     */
    protected function applyQueryFilter(Builder $query, array $filter = []): Builder
    {
        $cls = static::getItemClass();
        $model = new $cls();
        $helper = new QueryHelper();

        foreach ($model->getDates() as $dateAttr) {
            if (isset($filter[$dateAttr])) {
                if (is_array($filter[$dateAttr])) {
                    $filter[$dateAttr][1] = DateTrait::toStandartTime($filter[$dateAttr][1]);
                } else {
                    $filter[$dateAttr] = DateTrait::toStandartTime($filter[$dateAttr]);
                }
            }
        }

        $helper->apply($query, $filter, $model);

        return Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.filter'),
            $query
        );
    }
}
