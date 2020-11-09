<?php

namespace App\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Helpers\RecaptchaHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

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
    protected RecaptchaHelper $recaptcha;

    /**
     * Create a new AuthController instance.
     * @param RecaptchaHelper $recaptcha
     */
    public function __construct(RecaptchaHelper $recaptcha)
    {
        $this->recaptcha = $recaptcha;
        $this->middleware('auth:api')->except(['login', 'authDesktopKey']);
    }

    /**
     * @api            {post} /auth/login Login
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

        if (!$newToken = auth()->setTTL(config('auth.lifetime_minutes.jwt'))->attempt([
            'email' => $credentials['email'],
            'password' =>
                $credentials['password']
        ])) {
            $this->recaptcha->incrementCaptchaAmounts();
            $this->recaptcha->check($credentials);
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $user = auth()->user();
        if (!$user || !$user->active) {
            $this->recaptcha->incrementCaptchaAmounts();

            auth()->invalidate();
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

        return $this->respondWithToken($newToken);
    }

    /**
     * @api            {post} /auth/logout Logout
     * @apiDescription Invalidate JWT
     *
     * @apiVersion     1.0.0
     * @apiName        Logout
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Successfully logged out"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return new JsonResponse(['message' => 'Successfully logged out']);
    }

    /**
     * @api            {post} /auth/logout-from-all Logout from all
     * @apiDescription Invalidate all user JWT
     *
     * @apiVersion     1.0.0
     * @apiName        Logout all
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Successfully logged out from all sessions"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutFromAll(Request $request): JsonResponse
    {
        $user = $request->user();
        ++$user->nonce;
        $user->save();

        auth()->logout();

        return new JsonResponse(['message' => 'Successfully reset all sessions']);
    }

    /**
     * @api            {get} /auth/me Me
     * @apiDescription Get authenticated User Entity
     *
     * @apiVersion     1.0.0
     * @apiName        Me
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {Object}   user     User Entity
     *
     * @apiUse         UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "user": {
     *      "id": 1,
     *      "full_name": "Admin",
     *      "email": "admin@example.com",
     *      "url": "",
     *      "company_id": 1,
     *      "avatar": "",
     *      "screenshots_active": 1,
     *      "manual_time": 0,
     *      "computer_time_popup": 300,
     *      "blur_screenshots": 0,
     *      "web_and_app_monitoring": 1,
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
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return new JsonResponse(['user' => $request->user()]);
    }

    /**
     * @api            {post} /auth/refresh Refresh
     * @apiDescription Refreshes JWT
     *
     * @apiVersion     1.0.0
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
     * @throws Exception
     */
    public function refresh(): JsonResponse
    {
        $token = auth()->setTTL(config('auth.lifetime_minutes.jwt'))->refresh();

        return $this->respondWithToken($token);
    }

    /**
     * @api            {get} /auth/desktop-key Issue key
     * @apiDescription Issues key for desktop auth
     *
     * @apiVersion     1.0.0
     * @apiName        Issue key
     * @apiGroup       Auth
     *
     * @apiUse         AuthHeader
     *
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {String}   expires_in    Token TTL 8601String Date
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "access_token": "r6nPiGocAWdD5ZF60dTkUboVAWSXsUScpp7m9x9R",
     *    "token_type": "desktop",
     *    "expires_in": "2020-12-26T14:18:32+00:00"
     *  }
     *
     * @apiUse         UnauthorizedError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function issueDesktopKey(Request $request): JsonResponse
    {
        $token = Str::random(40);

        $lifetime = now()->addMinutes(config('auth.lifetime_minutes.desktop_token'));

        cache([
            sha1($request->ip()) . ":$token" => $request->user()->id
        ], $lifetime);

        return $this->respondWithToken($token, 'desktop', config('auth.lifetime_minutes.desktop_token'));
    }

    /**
     * @api            {post} /auth/desktop-key Key auth
     * @apiDescription Exchange desktop key to JWT
     *
     * @apiVersion     1.0.0
     * @apiName        Key auth
     * @apiGroup       Auth
     *
     * @apiHeader {String} Desktop key for user auth
     * @apiHeaderExample {json} Desktop Key Header Example
     *  {
     *    "Authorization": "desktop r6nPiGocAWdD5ZF60dTkUboVAWSXsUScpp7m9x9R"
     *  }
     *
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {String}   expires_in    Token TTL 8601String Date
     * @apiSuccess {Object}   user          User Entity
     *
     * @apiUse         UserObject
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "access_token": "16184cf3b2510464a53c0e573c75740540fe...",
     *    "token_type": "bearer",
     *    "expires_in": "2020-12-26T14:18:32+00:00",
     *    "user": {}
     *  }
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function authDesktopKey(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');

        if (!$token) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $token = explode(' ', $token);

        if (count($token) !== 2 || $token[0] !== 'desktop' || !cache()->has(sha1($request->ip()) . ":$token[1]")) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        if (!auth()->byId(cache(sha1($request->ip()) . ":$token[1]")) || ((!$user = auth()->user()) && !$user->active)) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        return $this->respondWithToken(auth()->tokenById($user->id));
    }

    /**
     * Helper for structuring answer with token
     * @param string $token
     * @param string $tokenType
     * @param float|int $lifetime
     * @return JsonResponse
     */
    private function respondWithToken(
        string $token,
        string $tokenType = 'bearer',
        int $lifetime = null
    ): JsonResponse {
        return new JsonResponse([
            'access_token' => $token,
            'token_type' => $tokenType,
            'expires_in' => now()->addMinutes($lifetime ?? config('auth.lifetime_minutes.jwt'))->toIso8601String(),
            'user' => auth()->user(),
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
