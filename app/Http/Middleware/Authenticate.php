<?php

namespace App\Http\Middleware;

use App\Exceptions\Entities\AuthorizationException;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Lang;

class Authenticate extends BaseAuthenticate
{
    public const DEFAULT_USER_LANGUAGE = 'en';

    public function handle($request, Closure $next, ...$guards): mixed
    {
        $this->authenticate($request, $guards);

        if (!$request->user()->active) {
            $request->user()->tokens()->whereId($request->user()->currentAccessToken()->id)->delete();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        Lang::setLocale($request->user()->user_language ?: self::DEFAULT_USER_LANGUAGE);
        return $next($request);
    }
}
