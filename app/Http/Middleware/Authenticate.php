<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class Authenticate extends \Illuminate\Auth\Middleware\Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Access denied', 'reason' => 'not logined'], 403);
        }

        // Check token.
        $auth = explode(' ', $request->header('Authorization'));
        if (!empty($auth) && count($auth) > 1 && $auth[0] == 'bearer') {
            $token = $auth[1];
            $token = DB::table('tokens')
                ->where('user_id', auth()->user()->id)
                ->where('token', $token)
                ->where('expires_at', '>', time())
                ->first();
            if (!isset($token)) {
                //return response()->json(['error' => 'Access denied', 'reason' => 'token blacklisted'], 403);
            }
        }

        return $next($request);
    }
}
