<?php

namespace App\Http\Middleware;

use App\Exceptions\Entities\AuthorizationException;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Auth;
use DB;
use Lang;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;

class Authenticate extends BaseAuthenticate
{
    public const DEFAULT_USER_LANGUAGE = 'en';

    /**
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$guards
     *
     * @return JsonResponse|mixed
     * @throws AuthorizationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            JWTAuth::parseToken()->getClaim('exp');
        } catch (JWTException $exception) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        if (!Auth::check()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $user = Auth::user();

        if (!$user || !$user->active) {
            DB::table('tokens')->where('user_id', $user->id)->delete();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        Lang::setLocale($user->user_language ? $user->user_language : self::DEFAULT_USER_LANGUAGE);
        return $next($request);
    }
}
