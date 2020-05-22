<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\v1\Invitation\CreateInvitationRequest;
use App\Http\Requests\v1\Invitation\UpdateInvitationRequest;
use App\Models\Invitation;
use App\Services\InvitationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends ItemController
{
    /**
     * @var InvitationService
     */
    protected InvitationService $service;

    /**
     * InvitationController constructor.
     * @param InvitationService $service
     */
    public function __construct(InvitationService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    /**
     * Get the controller rules.
     *
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'invitation.list',
            'count' => 'invitation.list',
            'create' => 'invitation.create',
            'resend' => 'invitation.resend',
            'show' => 'invitation.show',
            'destroy' => 'invitation.remove',
        ];
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * Get the event unique name part.
     *
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'invitation';
    }

    /**
     * Get the model class.
     *
     * @return string
     */
    public function getItemClass(): string
    {
        return Invitation::class;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @api             {post} /invitations/create Create
     * @apiDescription  Creates a unique invitation token and sends an email to the users
     *
     * @apiVersion      1.0.0
     * @apiName         CreateInvitation
     * @apiGroup        UserInvited
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   register_create
     *
     * @apiParam {Array} email  List of emails for new users
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "users": [
     *      {
     *          email: "test@example.com",
     *          role_id: 1
     *     }
     *  ]
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   res      Array of records containing the id, email, expiration date and key
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
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
     * @apiErrorExample {json} Email is not specified
     *  HTTP/1.1 400 Bad Request
     *  {
     *     "success": false,
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
     *      "success": false,
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
     *
     */
    public function create(Request $request): JsonResponse
    {
        $requestData = app(CreateInvitationRequest::class)->validated();

        $invitations = [];

        foreach ($requestData['users'] as $user) {
            $invitations[] = $this->service->create($user);
        }

        return new JsonResponse([
            'success' => true,
            'res' => $invitations,
        ]);
    }

    /**
     * @param UpdateInvitationRequest $request
     * @return JsonResponse
     * @throws Exception
     *
     * @api             {get} /invitations/resend Post
     * @apiDescription  resends the expiration time of the invitation.
     *
     * @apiVersion      1.0.0
     * @apiName         UpdateInvitation
     * @apiGroup        UserInvited
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   register_resend
     *
     * @apiParam {Integer}  id  UserInvited id
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Array}    res      UserInvited record
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *  }
     *
     * @apiErrorExample {json} The id does not exist
     *  HTTP/1.1 400 Bad Request
     *  {
     *      "success": false,
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
        $requestData = $request->validated();

        $invitation = $this->service->update($requestData['id']);

        return new JsonResponse([
            'success' => true,
            'res' => $invitation,
        ]);
    }
}
