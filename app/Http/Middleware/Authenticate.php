<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Closure;


class Authenticate extends \Illuminate\Auth\Middleware\Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {

        $path = $request->path();


        $matches = [];

        if(!Auth::check()) {
            return response()->json(['error' => 'Access denied', 'reason' => 'not logined'], 403);
        }

        return $next($request);
    }
}
