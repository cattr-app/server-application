<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Settings;

/**
 * Class RegistrationController
 * @codeCoverageIgnore until it is implemented on frontend
 */
class RegistrationController extends Controller
{
    /**
     * @param $key
     * @return JsonResponse
     * @api             {get} /auth/register/{key} Get Form
     * @apiDescription  Returns invitation form data by a invitation token
     *
     * @apiVersion      1.0.0
     * @apiName         GetRegistration
     * @apiGroup        Invitation
     *
     * @apiParam (Parameters from url) {String}  key  User invitation key
     *
     * @apiSuccess {String}   email    UserInvited email
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "email": "test@example.com"
     *  }
     *
     * @apiErrorExample {json} Email not found
     *  HTTP/1.1 404 Not found
     *  {
     *    "error": "Not found"
     *  }
     *
     * @apiUse          400Error
     *
     */
    public function getForm($key): JsonResponse
    {
        $invitation = Invitation::where('key', $key)
            ->where('expires_at', '>=', time())
            ->first();

        if (!isset($invitation)) {
            return new JsonResponse([
                'message' => __('The specified key has expired or does not exist')
            ], 404);
        }

        return responder()->success(['email' => $invitation->email])->respond();
    }

    /**
     * Creates a new user.
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     * @api             {post} /auth/register/{key} Post Form
     * @apiDescription  Registers user by key
     *
     * @apiVersion      1.0.0
     * @apiName         PostRegistration
     * @apiGroup        Invitation
     *
     * @apiParam (Parameters from url) {String}  key  User invitation key
     *
     * @apiParam {String}  email     New user email
     * @apiParam {String}  password  New user password
     * @apiParam {String}  fullName  New user name
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email": "johndoe@example.com",
     *    "password": "amazingpassword",
     *    "fullName": "John Doe"
     *  }
     *
     * @apiSuccess {Number}   user_id  New user ID
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "user_id": 2
     *  }
     *
     * @apiErrorExample {json} Email not found
     *  HTTP/1.1 404 Not found
     *  {
     *    "message": "The specified key has expired or does not exist"
     *  }
     *
     * @apiErrorExample {json} Email mismatch
     *  HTTP/1.1 400 Bad request
     *  {
     *    "message": "The email address does not match the key"
     *  }
     *
     * @apiUse          400Error
     *
     */
    public function postForm(Request $request, string $key): JsonResponse
    {
        $invitation = Invitation::where('key', $key)
            ->where('expires_at', '>=', time())
            ->first();

        if (!isset($invitation)) {
            return new JsonResponse([
                'message' => __('The specified key has expired or does not exist'),
            ], 404);
        }

        if ($request->input('email') !== $invitation->email) {
            return new JsonResponse([
                'message' => __('The email address does not match the key'),
            ], 400);
        }

        $language = Settings::scope('core')->get('language', 'en');

        /** @var User $user */
        $user = User::create([
            'full_name' => $request->input('full_name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'active' => true,
            'manual_time' => false,
            'screenshots_active' => true,
            'computer_time_popup' => 3,
            'screenshots_interval' => 10,
            'role_id' => $invitation->role_id,
            'user_language' => $language,
        ]);

        $invitation->delete();

        return responder()->success(['user_id' => $user->id])->respond();
    }
}
