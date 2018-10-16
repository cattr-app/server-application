<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\RelationsUsers;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

class RelationsUsersController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return RelationsUsers::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'user_id'          => 'required|exists:users,id',
            'attached_user_id' => 'required|exists:users,id',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'attached-users';
    }

    /**
     * @api {any} /api/v1/attached-users/list List
     * @apiDescription Get list of Attached Users relations
     * @apiVersion 0.1.0
     * @apiName GetAttachedUsersList
     * @apiGroup AttachedUsers
     *
     * @apiParam {Integer} [attached_user_id] `QueryParam` Attached user ID
     * @apiParam {Integer} [user_id]          `QueryParam` User ID
     *
     * @apiP
     * [
     *    {
     *      "user_id": 1,
     *      "attached_user_id": 1,
     *      "created_at": "2018-09-28 13:53:57",
     *      "updated_at": "2018-09-28 13:53:59"
     *    }
     * ]
     *
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/attached-users/create Create
     * @apiDescription Create Attached Users relation
     * @apiVersion 0.1.0
     * @apiName CreateAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam user_id Integer User ID
     * @apiParam attached_user_id Attached to User
     *
     * @apiRequestExample {json} Request
     * {
     *   "user_id": 1,
     *   "attached_user_id": 1
     * }
     *
     *
     * @apiSuccessAnswer {json} Answer Example:
     * [
     *   {
     *     "user_id": 1,
     *     "attached_user_id": 1,
     *     "updated_at": "2018-10-01 08:41:37",
     *     "created_at": "2018-10-01 08:41:37",
     *     "id": 0
     *   }
     * ]
     *
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request) : JsonResponse
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

        $item = Filter::process(
            $this->getEventUniqueName('item.create'),
            $cls::firstOrCreate($this->filterRequestData($requestData))
        );

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                $item,
            ])
        );
    }

    /**
     * @api {post} /api/v1/attached-users/bulk-create BulkCreate
     * @apiDescription Multiple Create Attached Users relation
     * @apiVersion 0.1.0
     * @apiName BulkCreateAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam {Relations[]} array                      Array of object Attached User relation
     * @apiParam {Object}      array.object               Object Attached User relation
     * @apiParam {Integer}     array.object.attached_user Attached User ID
     * @apiParam {Integer}     array.object.user_id       User ID
     *
     * @todo: add examples for request and success answer
     * @todo: add errors and params
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkCreate(Request $request) : JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(
                Filter::process(
                    $this->getEventUniqueName('answer.error.item.bulkEdit'), [
                        'error' => 'validation fail',
                        'reason' => 'relations is empty'
                    ]
                ),
                400
            );
        }

        foreach ($requestData['relations'] as $relation) {
            $validator = Validator::make(
                $relation,
                Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
            );

            if ($validator->fails()) {
                $result[] = Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors(),
                    'code' => 400
                ]);
                continue;
            }

            $cls = $this->getItemClass();

            $item = Filter::process(
                $this->getEventUniqueName('item.create'),
                $cls::firstOrCreate($this->filterRequestData($relation))
            );

            $result[] = $item;
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'messages' => $result,
            ])
        );
    }

    /**
     * @api {post} /api/v1/attached-users/remove Destroy
     * @apiDescription Destroy Attached Users relation
     * @apiVersion 0.1.0
     * @apiName DestroyAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam {Integer} User Relation ID
     *
     * @apiParamExample {json} Example Request:
     * {
     *   "user_id": 1,
     *   "attached_user_id": 1
     * }
     *
     * @failExample
     * {
     *   "error": "Item has not been removed",
     *   "reason": "Item not found"
     * }
     *
     * @todo: add examples for request and success answer
     * @todo: add errors and params
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request) : JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->all());

        $validator = Validator::make(
            $requestData,
            Filter::process(
                $this->getEventUniqueName('validation.item.edit'),
                $this->getValidationRules()
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

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(),
                $requestData
            )
        );

        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = $itemsQuery->first();
        if ($item) {
            $item->delete();
        } else {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                    'error' => 'Item has not been removed',
                    'reason' => 'Item not found'
                ]),
                404
            );
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }

    /**
     * @api {post} /api/v1/attached-users/bulk-destroy BulkDestroy
     * @apiDescription Multiple Destroy Attached Users relation
     * @apiVersion 0.1.0
     * @apiName BulkDestroyAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam {Relations[]} array                      Array of object Project User relation
     * @apiParam {Object}      array.object               Object Project User relation
     * @apiParam {Integer}     array.object.attached_user Attached User ID
     * @apiParam {Integer}     array.object.user_id       User ID
     *
     * @todo: add examples for request and success answer
     * @todo: add errors and params
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request) : JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(
                Filter::process(
                    $this->getEventUniqueName('answer.error.item.bulkEdit'), [
                        'error' => 'validation fail',
                        'reason' => 'relations is empty'
                    ]
                ),
                400
            );
        }

        foreach ($requestData['relations'] as $relation) {
            /** @var Builder $itemsQuery */
            $itemsQuery = Filter::process(
                $this->getEventUniqueName('answer.success.item.query.prepare'),
                $this->applyQueryFilter(
                    $this->getQuery(),
                    $relation
                )
            );

            $validator = Validator::make(
                $relation,
                Filter::process(
                    $this->getEventUniqueName('validation.item.edit'),
                    $this->getValidationRules()
                )
            );

            if ($validator->fails()) {
                $result[] = [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors(),
                    'code' => 400
                ];
                continue;
            }

            /** @var \Illuminate\Database\Eloquent\Model $item */
            $item = $itemsQuery->first();
            if ($item && $item->delete()) {
                $result[] = ['message' => 'Item has been removed'];
            } else {
                $result[] = [
                    'error' => 'Item has not been removed',
                    'reason' => 'Item not found'
                ];
            }
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'messages' => $result
            ])
        );
    }
}
