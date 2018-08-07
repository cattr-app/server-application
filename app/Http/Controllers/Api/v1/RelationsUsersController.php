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
            'user_id'          => 'required',
            'attached_user_id' => 'required',
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
     * @api {post} /api/v1/attached-users/remove Destroy
     * @apiDescription Destroy Attached Users relation
     * @apiVersion 0.1.0
     * @apiName DestroyAttachedUsers
     * @apiGroup AttachedUsers
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
                ])
            );
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }
}
