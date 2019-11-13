<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use App\Exceptions\Entities\AuthorizationException;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class Authenticate
 * @package App\Http\Middleware
 */
class Authenticate extends BaseAuthenticate
{
    /**
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$guards
     * @return JsonResponse|mixed
     * @throws AuthorizationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if (!auth()->check()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }
        /** @var User $user */
        $user = auth()->user();

        if (!$user->active) {
            $user->tokens()->delete();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        if (!request()->bearerToken()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_TOKEN_MISMATCH);
        }

        return $next($request);
    }
}
