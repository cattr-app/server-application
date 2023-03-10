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
     *
     * @apiSuccess {Object}   data  Token
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
     * @apiSuccess {Object}   data     User Entity
     *
     * @apiUse         UserObject
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    public function me(Request $request): JsonResponse
    {
        return responder()->success($request->user())->respond();
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
     * @deprecated Exists only for compatibility with old Cattr client
     */
    public function refresh(): JsonResponse
    {
        throw new DeprecatedApiException();
    }
}
