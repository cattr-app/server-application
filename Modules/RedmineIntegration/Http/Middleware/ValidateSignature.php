<?php

namespace Modules\RedmineIntegration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-REDMINE-SIGNATURE') !== config('redmineintegration.request.signature')) {
            return response('ERR_INVALID_SIGN', 403);
        }
        return $next($request);
    }
}
