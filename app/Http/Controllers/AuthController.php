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
     * @apiDefine AuthHeader
     * @apiHeader {String} Authorization Token for user auth
     * @apiHeaderExample {json} Authorization Header Example
     *  {
     *    "Authorization":  "bearer 16184cf3b2510464a53c0e573c75740540fe..."
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
     *
     * @api {post} /api/auth/login Login
     * @apiDescription Get user JWT
     *
     * @apiVersion 0.1.0
     * @apiName Login
     * @apiGroup Auth
     *
     * @apiParam {String}  email        User email
     * @apiParam {String}  password     User password
     * @apiParam {String}  [recaptcha]  Recaptcha token
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email":      "johndoe@example.com",
     *    "password":   "amazingpassword",
     *    "recaptcha":  "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
     *  }
     *
     * @apiSuccess {Boolean}  success       Indicates successful request when TRUE
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {String}   expires_in    Token TTL in seconds
     * @apiSuccess {Object}   user          User Entity
     *
     * @apiSuccessExample {json} Success Response
     *  HTTP/1.1 200 OK
     *  {
     *    "success":      true,
     *    "access_token": "16184cf3b2510464a53c0e573c75740540fe...",
     *    "token_type":   "bearer",
     *    "expires_in":   "3600",
     *    "user":         {}
     *  }
     *
     * @apiUse 400Error
     * @apiUse ParamsValidationError
     * @apiUse UnauthorizedError
     * @apiUse UserDeactivatedError
     * @apiUse CaptchaError
     * @apiUse LimiterError
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

        if (!$newToken = auth()->attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
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
            'success' => true,
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => $token->expires_at,
            'user' => $user
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @api {post} /api/auth/logout Logout
     * @apiDescription Invalidate JWT
     *
     * @apiVersion 0.1.0
     * @apiName Logout
     * @apiGroup Auth
     *
     * @apiUse AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when TRUE
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Success Response
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Successfully logged out"
     *  }
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->invalidateToken($request->bearerToken());
        auth()->logout();

        return response()->json(['success' => true, 'message' => 'Successfully logged out']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @api {post} /api/auth/logout Logout all
     * @apiDescription Invalidate all user JWT
     *
     * @apiVersion 0.1.0
     * @apiName Logout all
     * @apiGroup Auth
     *
     * @apiUse AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when TRUE
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Success Response
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Successfully ended all sessions"
     *  }
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
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
     *
     * @api {get} /api/auth/me Me
     * @apiDescription Get authenticated User Entity
     *
     * @apiVersion 0.1.0
     * @apiName Me
     * @apiGroup Auth
     *
     * @apiUse AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when TRUE
     * @apiSuccess {Array}    user     User Entity
     *
     * @apiSuccessExample {json} Answer Example
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
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'user' => $request->user()]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @api {post} /api/auth/refresh Refresh
     * @apiDescription Refreshes JWT
     *
     * @apiVersion 0.1.0
     * @apiName Refresh
     * @apiGroup Auth
     *
     * @apiUse AuthHeader
     *
     * @apiSuccess {Boolean}  success       Indicates successful request when TRUE
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {String}   expires_in    Token TTL in seconds
     * @apiSuccess {Array}    user          User Entity
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user $user */
        $user = $request->user();

        $user->invalidateToken($request->bearerToken());
        $token = auth()->refresh();
        $token = $user->addToken($token);

        return response()->json([
            'success' => true,
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => $token->expires_at,
            'user' => $user
        ]);
    }
}
