<?php

namespace App\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Helpers\RecaptchaHelper;
use App\Models\Token;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Validator;


/**
 * Class AuthController
 *
 * @package App\Http\Controllers
 */
class AuthController extends BaseController
{
    /**
     * @apiDefine UnauthorizedError
     *
     * @apiErrorExample {json} Access Error Example
     * {
     *    "error":      "Access denied",
     *    "reason":     "not logged in",
     *    "error_code": "ERR_NO_AUTH"
     * }
     *
     * @apiErrorExample {json} Access Error Example
     * {
     *    "error": "Unauthorized"
     * }
     *
     * @apiError (Error 403) {String} error         Error name
     * @apiError (Error 403) {String} reason        Error description
     * @apiError (Error 403) {String} error_code    Error code
     */

    /**
     * @apiDefine AuthAnswer
     *
     * @apiSuccess {String}     access_token  Token
     * @apiSuccess {String}     token_type    Token Type
     * @apiSuccess {String}     expires_in    Token TTL in seconds
     * @apiSuccess {Array}      user          User Entity
     *
     * @apiSuccessExample {json} Answer Example
     *  {
     *      {
     *        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciO...",
     *        "token_type": "bearer",
     *         "expires_in": 3600,
     *         "user": {
     *           "id": 42,
     *           "full_name": "Captain",
     *           "email": "johndoe@example.com",
     *           "url": "",
     *           "company_id": 41,
     *           "payroll_access": 1,
     *           "billing_access": 1,
     *           "avatar": "",
     *           "screenshots_active": 1,
     *           "manual_time": 0,
     *           "permanent_tasks": 0,
     *           "computer_time_popup": 300,
     *           "poor_time_popup": "",
     *           "blur_screenshots": 0,
     *           "web_and_app_monitoring": 1,
     *           "webcam_shots": 0,
     *           "screenshots_interval": 9,
     *           "active": "active",
     *           "deleted_at": null,
     *           "created_at": "2018-09-25 06:15:08",
     *           "updated_at": "2018-09-25 06:15:08",
     *           "timezone": null
     *         }
     *      }
     *  }
     */

    /**
     * @var RecaptchaHelper
     */
    protected $recaptcha;

    /**
     * Create a new AuthController instance.
     *
     * @param RecaptchaHelper $recaptcha
     */
    public function __construct(RecaptchaHelper $recaptcha)
    {
        $this->recaptcha = $recaptcha;
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @api {post} /api/auth/login Login
     * @apiDescription Get user JWT
     *
     *
     * @apiVersion 0.1.0
     * @apiName Login
     * @apiGroup Auth
     *
     * @apiParam {String}   login       User login
     * @apiParam {String}   password    User password
     * @apiParam {String}   recaptcha   Recaptcha token
     *
     * @apiSuccess {String}     access_token  Token
     * @apiSuccess {String}     token_type    Token Type
     * @apiSuccess {String}     expires_in    Token TTL in seconds
     * @apiSuccess {Array}      user          User Entity
     *
     * @apiError (Error 401) {String} Error Error
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "login":      "johndoe@example.com",
     *      "password":   "amazingpassword",
     *      "recaptcha":  "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
     *  }
     *
     * @apiUse AuthAnswer
     * @apiUse UnauthorizedError
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password', 'recaptcha']);

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success'=> false, 'message' => 'asd'], 400);
        }

        $this->recaptcha->check($credentials);

        if (!$newToken = auth()->attempt($credentials)) {
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

        return response()->json([
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => $token->expires_at,
            'user' => $user
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @param Request $request
     * @return JsonResponse
     * @api {any} /api/auth/logout Logout
     * @apiDescription Invalidate JWT
     * @apiVersion 0.1.0
     * @apiName Logout
     * @apiGroup Auth
     *
     * @apiSuccess {String}    message    Action result message
     *
     * @apiSuccessExample {json} Answer Example
     *  {
     *      "message": "Successfully logged out"
     *  }
     *
     * @apiUse UnauthorizedError
     *
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->invalidateToken($request->bearerToken());
        auth()->logout();

        return response()->json(['success' => true, 'message' => 'Successfully logged out']);
    }

    /**
     * Log the user out (Invalidate all tokens).
     *
     * @param Request $request
     * @return JsonResponse
     * @api {any} /api/auth/logout Logout
     * @apiDescription Invalidate JWT
     * @apiVersion 0.1.0
     * @apiName Logout
     * @apiGroup Auth
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "token": "eyJ0eXAiOiJKV1QiLCJhbGciO..."
     *  }
     *
     * @apiSuccess {String}    message    Action result message
     *
     * @apiSuccessExample {json} Answer Example
     *  {
     *      "message": "Successfully ended all sessions"
     *  }
     *
     * @apiUse UnauthorizedError
     *
     */
    public function logoutFromAll(Request $request): JsonResponse
    {
        $request->user()->invalidateAllTokens();
        auth()->logout();

        return response()->json(['success' => true, 'message' => 'Successfully logged out from all sessions']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @api {get} /api/auth/me Me
     * @apiDescription Get authenticated User Entity
     *
     * @apiVersion 0.1.0
     * @apiName Me
     * @apiGroup Auth
     *
     * @apiSuccess {String}     access_token  Token
     * @apiSuccess {String}     token_type    Token Type
     * @apiSuccess {String}     expires_in    Token TTL in seconds
     * @apiSuccess {Array}      user          User Entity
     *
     * @apiUse UnauthorizedError
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "id": 1,
     *   "full_name": "Admin",
     *   "email": "admin@example.com",
     *   "url": "",
     *   "company_id": 1,
     *   "payroll_access": 1,
     *   "billing_access": 1,
     *   "avatar": "",
     *   "screenshots_active": 1,
     *   "manual_time": 0,
     *   "permanent_tasks": 0,
     *   "computer_time_popup": 300,
     *   "poor_time_popup": "",
     *   "blur_screenshots": 0,
     *   "web_and_app_monitoring": 1,
     *   "webcam_shots": 0,
     *   "screenshots_interval": 9,
     *   "active": "active",
     *   "deleted_at": null,
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08",
     *   "timezone": null
     * }
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @api {post} /api/auth/refresh Refresh
     * @apiDescription Refresh JWT
     *
     * @apiVersion 0.1.0
     * @apiName Refresh
     * @apiGroup Auth
     *
     * @apiUse UnauthorizedError
     *
     * @apiUse AuthAnswer
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user $user */
        $user = $request->user();

        $user->invalidateToken($request->bearerToken());
        $token = auth()->refresh();
        $token = $user->addToken($token);

        return response()->json([
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => $token->expires_at,
            'user' => $user
        ]);
    }
}
