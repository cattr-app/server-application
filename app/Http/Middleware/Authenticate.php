<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use DB;
use App\Exceptions\Entities\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class Authenticate
 * @package App\Http\Middleware
 */
class Authenticate extends \Illuminate\Auth\Middleware\Authenticate
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
        if (!Auth::check()) {
            throw new AuthorizationException('Access denied', 'Not authorized');
        }

        $user = Auth::user();

        if (!$user || !$user->active) {
            DB::table('tokens')->where('user_id', $user->id)->delete();
            throw new AuthorizationException('Access denied', 'User deactivated');
        }

        // Check token.
        $auth = explode(' ', $request->header('Authorization'));

        if (!empty($auth) && count($auth) > 1 && $auth[0] === 'bearer') {
            $token = $auth[1];
            $token = DB::table('tokens')
                ->where('user_id', auth()->user()->id)
                ->where('token', $token)
                ->where('expires_at', '>', time())
                ->first()
            ;

            if (!isset($token)) {
                throw new AuthorizationException('Access denied', 'Token mismatch');
            }
        }

        return $next($request);
    }
}
