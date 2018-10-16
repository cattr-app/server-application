<?php

namespace App\Http\Controllers;

use App\Helpers\CatHelper;
use Illuminate\Http\JsonResponse;

/**
 * Class AuthController
 *
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * @apiDefine UnauthorizedError
     *
     * @apiErrorExample {json} Access Error Example:
     * {
     *    "error":      "Access denied",
     *    "reason":     "not logged in",
     *    "error_code": "ERR_NO_AUTH"
     * }
     *
     * @apiErrorExample {json} Access Error Example:
     * {
     *    "error": "Unauthorized"
     * }
     *
     * @apiError (Error 400) {String} error         Error name
     * @apiError (Error 400) {String} reason        Error description
     * @apiError (Error 400) {String} error_core    Error code
     */

    /**
     * @apiDefine AuthAnswer
     *
     * @apiSuccessExample {json} Answer Example:
     *  {
     *      {
     *        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciO...",
     *        "token_type": "bearer",
     *         "expires_in": 3600,
     *         "user": {
     *           "id": 42,
     *           "full_name": "Captain",
     *           "first_name": "John",
     *           "last_name": "Doe",
     *           "email": "johndoe@example.com",
     *           "url": "",
     *           "company_id": 41,
     *           "level": "admin",
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
     *           "user_role_value": "",
     *           "active": "active",
     *           "deleted_at": null,
     *           "created_at": "2018-09-25 06:15:08",
     *           "updated_at": "2018-09-25 06:15:08",
     *           "role_id": 1,
     *           "timezone": null
     *         }
     *      }
     *  }
     */

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => ['login', 'refresh']
        ]);
    }

    /**
     * @api {any} /api/auth/ping Ping
     * @apiDescription Get API status
     *
     * @apiVersion 0.1.0
     * @apiName Ping
     * @apiGroup Auth
     *
     * @apiSuccess {Integer}   status API HTTP-code status
     * @apiSuccess {Boolean}   error  Error
     * @apiSuccess {String}    cat    Sample Cat
     *
     * @apiSuccessExample {json} Answer Example:
     *  {
     *      "status": 200,
     *      "error":  false,
     *      "cat":    '(=ㅇ༝ㅇ=)',
     *  }
     *
     * @return JsonResponse
     */
    public function ping(): JsonResponse
    {
        $helper = new CatHelper();

        return response()->json([
            'status' => 200,
            'error' => false,
            'cat' => $helper->getCat(),
        ]);
    }

    /**
     * @api {post} /api/auth/login Login
     * @apiDescription Get user JWT
     *
     * @apiVersion 0.1.0
     * @apiName Login
     * @apiGroup Auth
     *
     * @apiParam {String}   login       User login
     * @apiParam {Integer}  password    User password
     *
     * @apiSuccess {String}     access_token  Token
     * @apiSuccess {String}     token_type    Token Type
     * @apiSuccess {String}     expires_in    Token TTL in seconds
     * @apiSuccess {Array}      user          User Entity
     *
     * @apiError (Error 401) {String} Error Error
     *
     * @apiParamExample {json} Request Example:
     *  {
     *      "login":      "johndoe@example.com",
     *      "password":   "amazingpassword",
     *  }
     *
     * @apiUse AuthAnswer
     * @apiUse UnauthorizedError
     *
     * @return JsonResponse
     */
    public function login(): JsonResponse
    {
        $credentials = request([
            'login',
            'password'
        ]);

        $data = [
            'email' => $credentials['login'] ?? null,
            'password' => $credentials['password'] ?? null,
        ];

        if (!$token = auth()->attempt($data)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @api {any} /api/auth/logout Logout
     * @apiDescription Invalidate JWT
     * @apiVersion 0.1.0
     * @apiName Logout
     * @apiGroup Auth
     *
     * @apiSuccess {String}    message    Action result message
     *
     * @apiSuccessExample {json} Answer Example:
     *  {
     *      "message": "Successfully logged out"
     *  }
     *
     * @apiUse UnauthorizedError
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
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
     * @apiSuccessExample {json} Answer Example:
     * {
     *     "id": 1,
     *     "full_name": "Admin",
     *     "first_name": "Ad",
     *     "last_name": "Min",
     *     "email": "admin@example.com",
     *     "url": "",
     *     "company_id": 1,
     *     "level": "admin",
     *     "payroll_access": 1,
     *     "billing_access": 1,
     *     "avatar": "",
     *     "screenshots_active": 1,
     *     "manual_time": 0,
     *     "permanent_tasks": 0,
     *     "computer_time_popup": 300,
     *     "poor_time_popup": "",
     *     "blur_screenshots": 0,
     *     "web_and_app_monitoring": 1,
     *     "webcam_shots": 0,
     *     "screenshots_interval": 9,
     *     "user_role_value": "",
     *     "active": "active",
     *     "deleted_at": null,
     *     "created_at": "2018-09-25 06:15:08",
     *     "updated_at": "2018-09-25 06:15:08",
     *     "role_id": 1,
     *     "timezone": null
     * }
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }

    /**
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
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}
