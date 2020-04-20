<?php

namespace App\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Helpers\RecaptchaHelper;
use App\Models\Token;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;


class AuthController extends BaseController
{
    /**
     * @apiDefine AuthHeader
     * @apiHeader {String} Authorization Token for user auth
     * @apiHeaderExample {json} Authorization Header Example
     *  {
     *    "Authorization": "bearer 16184cf3b2510464a53c0e573c75740540fe..."
     *  }
     */

    /**
     * @var RecaptchaHelper
     */
    protected $recaptcha;

    /**
     * Create a new AuthController instance.
     */
    public function __construct(RecaptchaHelper $recaptcha)
    {
        $this->recaptcha = $recaptcha;
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * @api            {post} /v1/auth/login Login
     * @apiDescription Get user JWT
     *
     * @apiVersion     1.0.0
     * @apiName        Login
     * @apiGroup       Auth
     *
     * @apiParam {String}  email        User email
     * @apiParam {String}  password     User password
     * @apiParam {String}  [recaptcha]  Recaptcha token
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email": "johndoe@example.com",
     *    "password": "amazingpassword",
     *    "recaptcha": "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
     *  }
     *
     * @apiSuccess {Boolean}  success       Indicates successful request when `TRUE`
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {ISO8601}  expires_in    Token TTL
     * @apiSuccess {Object}   user          User Entity
     *
     * @apiUse         UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "access_token": "16184cf3b2510464a53c0e573c75740540fe...",
     *    "token_type": "bearer",
     *    "expires_in": "2020-12-26T14:18:32+00:00",
     *    "user": {}
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ParamsValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         UserDeactivatedError
     * @apiUse         CaptchaError
     * @apiUse         LimiterError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password', 'recaptcha']);

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_VALIDATION_FAILED);
        }

        $this->recaptcha->check($credentials);

        if (!$newToken = auth()->setTTL(365 * 24 * 60)->attempt(['email' => $credentials['email'], 'password' =>
            $credentials['password']])) {
            $this->recaptcha->incrementCaptchaAmounts();
            $this->recaptcha->check($credentials);
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $user = auth()->user();
        if ($user && !$user->active) {
            $this->recaptcha->incrementCaptchaAmounts();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        /** @var Token $token */
        $token = $user->addToken($newToken);

        $this->recaptcha->clearCaptchaAmounts();

        return new JsonResponse([
            'success' => true,
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => Carbon::parse($token->expires_at)->toIso8601String(),
            'user' => $user
        ]);
    }

    /**
     * @api            {post} /v1/auth/logout Logout
     * @apiDescription Invalidate JWT
     *
     * @apiVersion     1.0.0
     * @apiName        Logout
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Successfully logged out"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */

    public function logout(Request $request): JsonResponse
    {
        $request->user()->invalidateToken($request->bearerToken());
        auth()->logout();

        return new JsonResponse(['success' => true, 'message' => 'Successfully logged out']);
    }

    /**
     * @api            {post} /v1/auth/logout-from-all Logout from all
     * @apiDescription Invalidate all user JWT
     *
     * @apiVersion     1.0.0
     * @apiName        Logout all
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Successfully logged out from all sessions"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */

    public function logoutFromAll(Request $request): JsonResponse
    {
        $request->user()->invalidateAllTokens();
        auth()->logout();

        return new JsonResponse(['success' => true, 'message' => 'Successfully logged out from all sessions']);
    }

    /**
     * @api            {get} /v1/auth/me Me
     * @apiDescription Get authenticated User Entity
     *
     * @apiVersion     1.0.0
     * @apiName        Me
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   user     User Entity
     *
     * @apiUse         UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "user": {
     *      "id": 1,
     *      "full_name": "Admin",
     *      "email": "admin@example.com",
     *      "url": "",
     *      "company_id": 1,
     *      "payroll_access": 1,
     *      "billing_access": 1,
     *      "avatar": "",
     *      "screenshots_active": 1,
     *      "manual_time": 0,
     *      "permanent_tasks": 0,
     *      "computer_time_popup": 300,
     *      "poor_time_popup": "",
     *      "blur_screenshots": 0,
     *      "web_and_app_monitoring": 1,
     *      "webcam_shots": 0,
     *      "screenshots_interval": 9,
     *      "active": "active",
     *      "deleted_at": null,
     *      "created_at": "2018-09-25 06:15:08",
     *      "updated_at": "2018-09-25 06:15:08",
     *      "timezone": null
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */

    public function me(Request $request): JsonResponse
    {
        return new JsonResponse(['success' => true, 'user' => $request->user()]);
    }

    /**
     * @api            {post} /v1/auth/refresh Refresh
     * @apiDescription Refreshes JWT
     *
     * @apiVersion     1.0.0
     * @apiName        Refresh
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {Boolean}  success       Indicates successful request when `TRUE`
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {String}   expires_in    Token TTL 8601String Date
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->invalidateToken($request->bearerToken());
        $token = auth()->refresh();
        $token = $user->addToken($token);

        return new JsonResponse([
            'success' => true,
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => Carbon::parse($token->expires_at)->toIso8601String(),
        ]);
    }

    /**
     * @apiDeprecated since 1.0.0 use now (#Password_Reset:Process)
     * @api {post} /api/auth/reset Reset
     * @apiDescription Get user JWT
     *
     *
     * @apiVersion 1.0.0
     * @apiName Reset
     * @apiGroup Auth
     */

    /**
     * @apiDeprecated since 1.0.0 use now (#Password_Reset:Request)
     * @api {post} /api/auth/send-reset Send reset e-mail
     * @apiDescription Get user JWT
     *
     *
     * @apiVersion 1.0.0
     * @apiName Send reset
     * @apiGroup Auth
     */
}
