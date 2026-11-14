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

    public function __construct(protected Recaptcha $recaptcha)
    {
    }

    /**
     * Login
     *
     * Get user Token
     *
     * `data.user` is the authenticated user, `data.expires_in` is `null`.
     * After failed attempts a reCAPTCHA token (`recaptcha`) may be required, see the `429` response.
     *
     * @response array{status: int, success: true, data: array{
     *     access_token: string,
     *     token_type: string,
     *     expires_in: string|null,
     *     user: \App\Models\User,
     * }}
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
     * Logout
     *
     * Invalidate current token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return responder()->success()->respond(204);
    }

    /**
     * Logout from all
     *
     * Invalidate all user tokens
     */
    public function logoutFromAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return responder()->success()->respond(204);
    }

    /**
     * Me
     *
     * Get authenticated User Entity
     */
    public function me(Request $request): JsonResponse
    {
        return responder()->success($request->user())->respond();
    }

    /**
     * Issue key
     *
     * Issues key for desktop auth
     *
     * `data.access_token` is a desktop key (not a bearer token) that is bound to the IP address of the
     * request and expires at `data.expires_in`. Exchange it with `PUT /auth/desktop-key`.
     * `frontend_uri` is the configured address of the web application (may be `null`).
     *
     * @response array{status: int, success: true, data: array{
     *     access_token: string,
     *     token_type: string,
     *     expires_in: string,
     *     user: \App\Models\User,
     * }, frontend_uri: string|null}
     *
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
     * Key auth
     *
     * Exchange desktop key to JWT
     *
     * Send the key from `GET /auth/desktop-key` as `Authorization: desktop <key>` from the same IP address.
     * Returns a bearer token of the key's user. The key is single-use: it is consumed by the exchange, a repeated
     * exchange answers `401`.
     *
     * @response array{status: int, success: true, data: array{
     *     access_token: string,
     *     token_type: string,
     *     expires_in: string|null,
     *     user: \App\Models\User,
     * }}
     *
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

        if (count($token) !== 2 || $token[0] !== 'desktop') {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        // The key is single-use: pull() reads and removes it, so a repeated exchange is unauthorized
        $userId = Cache::store('octane')->pull(sha1($request->ip()) . ":$token[1]");

        if ($userId === null) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $user = auth()->loginUsingId($userId);

        if (!optional($user)->active) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        return responder()->success([
            'token' => $user->createToken(Str::uuid())->plainTextToken,
        ], new AuthTokenTransformer)->respond();
    }

    /**
     * Refresh
     *
     * Refreshes JWT
     *
     * Always fails with `400` (`error.code` is `deprecation.api`), tokens are not refreshed anymore.
     *
     * @deprecated Exists only for compatibility with old Cattr client
     */
    public function refresh(): JsonResponse
    {
        throw new DeprecatedApiException();
    }
}
