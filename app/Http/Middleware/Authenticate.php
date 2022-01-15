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

        if (!optional(auth()->user())->active) {
            optional(optional(auth()->user())->currentAccessToken())->delete();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        Lang::setLocale(optional(auth()->user())->user_language ?: self::DEFAULT_USER_LANGUAGE);
        return $next($request);
    }
}
