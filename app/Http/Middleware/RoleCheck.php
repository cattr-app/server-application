<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $path = $request->path();


        $matches = [];

        $w = '[a-zA-Z\\-_0-9]+';

        if(Auth::check() && preg_match("#^api/v1/({$w})/({$w})#", $request->path(), $matches)) {
            // is api request
            $object = $matches[1];
            $action = $matches[2];


            if(!Role::can(Auth::user(),$object , $action)) {
                return response()->json(['error' => "Access denied to $object/$action", 'reason' => 'action is not allowed'], 403);
            }
        }

        return $next($request);
    }
}
