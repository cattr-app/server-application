<?php

namespace App\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Exceptions\Entities\DeprecatedApiException;
use App\Helpers\Recaptcha;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Transformers\AuthTokenTransformer;
use Cache;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

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

    public function __construct(protected Recaptcha $recaptcha)
    {
    }

    /**
     * @api            {post} /auth/login Login
     * @apiDescription Get user Token
     *
     * @apiVersion     4.0.0
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
     * @apiUse         User
     * @apiUse         400Error
     * @apiUse         ParamsValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         UserDeactivatedError
     * @apiUse         CaptchaError
     * @apiUse         LimiterError
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password', 'recaptcha']);

        $this->recaptcha->check($credentials);

        if (!auth()->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            $this->recaptcha->incrementCaptchaAmounts();
            $this->recaptcha->check($credentials);
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $user = auth()->user();
        if (!$user || !$user->active) {
            $this->recaptcha->incrementCaptchaAmounts();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        if ($user->invitation_sent) {
            $user->invitation_sent = false;
            $user->save();
        }

        $this->recaptcha->clearCaptchaAmounts();

        if (preg_match('/' . config('auth.cattr-client-agent') . '/', $request->header('User_agent'))) {
            $user->client_installed = 1;
            $user->save();
        }

        return responder()->success([
            'token' => $user->createToken(Str::uuid())->plainTextToken,
        ], new AuthTokenTransformer)->respond();
    }

    /**
     * @api            {post} /auth/logout Logout
     * @apiDescription Invalidate current token
     *
     * @apiVersion     4.0.0
     * @apiName        Logout
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 204 OK
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return responder()->success()->respond(204);
    }

    /**
     * @api            {post} /auth/logout-from-all Logout from all
     * @apiDescription Invalidate all user tokens
     *
     * @apiVersion     4.0.0
     * @apiName        Logout all
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 204 OK
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    public function logoutFromAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return responder()->success()->respond(204);
    }

    /**
     * @api            {get} /auth/me Me
     * @apiDescription Get authenticated User Entity
     *
     * @apiVersion     4.0.0
     * @apiName        Me
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {Integer}  id                   ID of the user
     * @apiSuccess {String}   full_name            Full name of the user
     * @apiSuccess {String}   email                Email of the user
     * @apiSuccess {String}   [url]                URL of the user (optional)
     * @apiSuccess {Integer}  company_id           Company ID of the user
     * @apiSuccess {String}   [avatar]             Avatar URL of the user (optional)
     * @apiSuccess {Boolean}  screenshots_active   Indicates if screenshots are active
     * @apiSuccess {Boolean}  manual_time          Indicates if manual time tracking is allowed
     * @apiSuccess {Integer}  computer_time_popup  Time interval for computer time popup
     * @apiSuccess {Boolean}  blur_screenshots     Indicates if screenshots are blurred
     * @apiSuccess {Boolean}  web_and_app_monitoring Indicates if web and app monitoring is enabled
     * @apiSuccess {Integer}  screenshots_interval Interval for taking screenshots
     * @apiSuccess {Boolean}  active               Indicates if the user is active
     * @apiSuccess {String}   [deleted_at]         Deletion timestamp (if applicable, otherwise null)
     * @apiSuccess {String}   created_at           Creation timestamp
     * @apiSuccess {String}   updated_at           Last update timestamp
     * @apiSuccess {String}   [timezone]           Timezone of the user (optional)
     * @apiSuccess {Boolean}  important            Indicates if the user is marked as important
     * @apiSuccess {Boolean}  change_password      Indicates if the user needs to change password
     * @apiSuccess {Integer}  role_id              Role ID of the user
     * @apiSuccess {String}   user_language        Language of the user
     * @apiSuccess {String}   type                 Type of the user (e.g., "employee")
     * @apiSuccess {Boolean}  invitation_sent      Indicates if invitation is sent to the user
     * @apiSuccess {Integer}  nonce                Nonce value of the user
     * @apiSuccess {Boolean}  client_installed     Indicates if client is installed
     * @apiSuccess {Boolean}  permanent_screenshots Indicates if screenshots are permanent
     * @apiSuccess {String}   last_activity        Last activity timestamp of the user
     * @apiSuccess {Boolean}  online               Indicates if the user is online
     * @apiSuccess {Boolean}  can_view_team_tab    Indicates if the user can view team tab
     * @apiSuccess {Boolean}  can_create_task      Indicates if the user can create tasks
     *
     * @apiSuccessExample {json} Response Example:
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 1,
     *      "full_name": "Admin",
     *      "email": "admin@cattr.app",
     *      "url": "",
     *      "company_id": 1,
     *      "avatar": "",
     *      "screenshots_active": 1,
     *      "manual_time": 0,
     *      "computer_time_popup": 300,
     *      "blur_screenshots": false,
     *      "web_and_app_monitoring": true,
     *      "screenshots_interval": 5,
     *      "active": 1,
     *      "deleted_at": null,
     *      "created_at": "2023-10-26T10:26:17.000000Z",
     *      "updated_at": "2024-02-15T19:06:42.000000Z",
     *      "timezone": null,
     *      "important": 0,
     *      "change_password": 0,
     *      "role_id": 0,
     *      "user_language": "en",
     *      "type": "employee",
     *      "invitation_sent": false,
     *      "nonce": 0,
     *      "client_installed": 0,
     *      "permanent_screenshots": 0,
     *      "last_activity": "2023-10-26 10:26:17",
     *      "online": false,
     *      "can_view_team_tab": true,
     *      "can_create_task": true
     * }
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     */
    public function me(Request $request): JsonResponse
    {
        return responder()->success($request->user())->respond();
    }

    /**
     * @api            {get} /auth/desktop-key Issue key
     * @apiDescription Issues key for desktop auth
     *
     * @apiVersion     4.0.0
     * @apiName        Issue key
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     * @apiUse         User
     * @apiUse         UnauthorizedError
     */
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function issueDesktopKey(Request $request): JsonResponse
    {
        $token = Str::random(40);

        $lifetime = now()->addMinutes(config('auth.lifetime_minutes.desktop_token'));

        Cache::store('octane')->put(
            sha1($request->ip()) . ":$token",
            $request->user()->id,
            $lifetime,
        );
        return responder()->success([
            'token' => $token,
            'type' => 'desktop',
            'expires' => now()->addMinutes(config('auth.lifetime_minutes.desktop_token'))->toIso8601String(),
        ], new AuthTokenTransformer)->meta(['frontend_uri' => config('app.frontend_url')])->respond();
    }

    /**
     * @api            {put} /auth/desktop-key Key auth
     * @apiDescription Exchange desktop key to JWT
     *
     * @apiVersion     4.0.0
     * @apiName        Key auth
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     * @apiUse         User
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function authDesktopKey(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');

        if (!$token) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $token = explode(' ', $token);

        if (count($token) !== 2 || $token[0] !== 'desktop' || !Cache::store('octane')->has(sha1($request->ip()) . ":$token[1]")) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $user = auth()->loginUsingId(Cache::store('octane')->get(sha1($request->ip()) . ":$token[1]"));

        if (!optional($user)->active) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        return responder()->success([
            'token' => $user->createToken(Str::uuid())->plainTextToken,
        ], new AuthTokenTransformer)->respond();
    }

    /**
     * @apiDeprecated Exists only for compatibility with old Cattr client
     * @api            {post} /auth/refresh Refresh
     * @apiDescription Refreshes JWT
     *
     * @apiVersion     4.0.0
     * @apiName        Refresh
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {String}   expires_in    Token TTL 8601String Date
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */

    /**
     * @return JsonResponse
     * @deprecated Exists only for compatibility with old Cattr client
     */
    public function refresh(): JsonResponse
    {
        throw new DeprecatedApiException();
    }
}
