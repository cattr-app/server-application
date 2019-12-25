<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class CheckTokenExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure                   $next
     *
     * @return mixed
     * @throws JWTException
     */
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            JWTAuth::parseToken()->getClaim('exp');
        }
        return $next($request);
    }
}
