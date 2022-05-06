<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Invitation\CreateInvitationRequest;
use App\Http\Requests\Invitation\ListInvitationRequest;
use App\Http\Requests\Invitation\DestroyInvitationRequest;
use App\Http\Requests\Invitation\ShowInvitationRequest;
use App\Http\Requests\Invitation\UpdateInvitationRequest;
use App\Models\Invitation;
use App\Services\InvitationService;
use Exception;
use Filter;
use Illuminate\Http\JsonResponse;
use Throwable;

class InvitationController extends ItemController
{
    protected const MODEL = Invitation::class;

    /**
     * @throws Throwable
     * @api             {post} /invitations/show Show
     * @apiDescription  Show invitation.
     *
     * @apiVersion      1.0.0
     * @apiName         Show Invitation
     * @apiGroup        Invitation
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer} id  Invitation ID
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Array}    res      Array of records containing the id, email, key, expiration date and role id
     *
     * @apiUse InvitationObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": [
     *      {
     *          "id": 1
     *          "email": "test@example.com",
     *          "key": "06d4a090-9675-11ea-bf39-5f84c549e29c",
     *          "expires_at": "2020-01-01T00:00:00.000000Z",
     *          "role_id": 1
     *      }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function show(ShowInvitationRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @throws Exception
     * @api             {get} /invitations/list List
     * @apiDescription  Get list of invitations.
     *
     * @apiVersion      1.0.0
     * @apiName         Invitation List
     * @apiGroup        Invitation
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Array}   res      Array of records containing the id, email, key, expiration date and role id
     *
     * @apiUse InvitationObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": [
     *      {
     *          "id": 1
     *          "email": "test@example.com",
     *          "key": "06d4a090-9675-11ea-bf39-5f84c549e29c",
     *          "expires_at": "2020-01-01T00:00:00.000000Z",
     *          "role_id": 1
     *      }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function index(ListInvitationRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * @param CreateInvitationRequest $request
     * @return JsonResponse
     * @throws Exception
     * @api             {post} /invitations/create Create
     * @apiDescription  Creates a unique invitation token and sends an email to the users
     *
     * @apiVersion      1.0.0
     * @apiName         Create Invitation
     * @apiGroup        Invitation
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Array}    users          List of users to send an invitation to
     * @apiParam {String}   users.email    User email
     * @apiParam {Integer}  users.role_id  ID of the role that will be assigned to the created user
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "users": [
     *      {
     *        email: "test@example.com",
     *        role_id: 1
     *      }
     *    ]
     *  }
     *
     * @apiSuccess {String}   res      Array of records containing the id, email, key, expiration date and role id
     *
     * @apiUse InvitationObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": [
     *      {
     *          "id": 1
     *          "email": "test@example.com",
     *          "key": "06d4a090-9675-11ea-bf39-5f84c549e29c",
     *          "expires_at": "2020-01-01T00:00:00.000000Z",
     *          "role_id": 1
     *      }
     *    ]
     *  }
     *
     * @apiErrorExample {json} Email is not specified
     *  HTTP/1.1 400 Bad Request
     *  {
     *     "error_type": "validation",
     *     "message": "Validation error",
     *     "info": {
     *          "users.0.email": [
     *              "The email field is required."
     *         ]
     *     }
     * }
     *
     * @apiErrorExample {json} Email already exists
     *  HTTP/1.1 400 Bad Request
     *  {
     *      "error_type": "validation",
     *      "message": "Validation error",
     *      "info": {
     *          "users.0.email": [
     *              "The email test@example.com has already been taken."
     *          ]
     *      }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     */
    public function create(CreateInvitationRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $invitations = [];

        foreach ($requestData['users'] as $user) {
            $invitations[] = InvitationService::create($user);
        }

        return responder()->success($invitations)->respond();
    }

    /**
     * @param UpdateInvitationRequest $request
     * @return JsonResponse
     * @throws Exception
     *
     * @api             {post} /invitations/resend Resend
     * @apiDescription  Updates the token expiration date and sends an email to the user's email address.
     *
     * @apiVersion      1.0.0
     * @apiName         Update Invitation
     * @apiGroup        Invitation
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}  id  Invitation ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1
     *  }
     *
     * @apiSuccess {Array}    res      Invitation data
     *
     * @apiUse InvitationObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1
     *      "email": "test@example.com",
     *      "key": "06d4a090-9675-11ea-bf39-5f84c549e29c",
     *      "expires_at": "2020-01-01T00:00:00.000000Z",
     *      "role_id": 1
     *    }
     *  }
     *
     * @apiErrorExample {json} The id does not exist
     *  HTTP/1.1 400 Bad Request
     *  {
     *      "error_type": "validation",
     *      "message": "Validation error",
     *      "info": {
     *          "id": [
     *              "The selected id is invalid."
     *          ]
     *      }
     * }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function resend(UpdateInvitationRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $invitation = InvitationService::update($requestData['id']);

        return responder()->success($invitation)->respond();
    }

    /**
     * @throws Throwable
     * @api             {post} /invitations/remove Destroy
     * @apiDescription  Destroy User
     *
     * @apiVersion      1.0.0
     * @apiName         Destroy Invitation
     * @apiGroup        Invitation
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}  id  ID of the target invitation
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function destroy(DestroyInvitationRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @param ListInvitationRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function count(ListInvitationRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
