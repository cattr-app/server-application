<?php

namespace Modules\RedmineIntegration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-REDMINE-SIGNATURE') != config('redmineintegration.request.signature')) {
            return response('ERR_INVALID_SIGN', 403);
        }
        return $next($request);
    }

    /**
     * @param  Request  $request
     * @deprecated
     *
     * @return string
     */
    protected function generateSignature(Request $request): string
    {
        return hash_hmac("SHA256", config('redmineintegration.request.signature'),
            json_encode($request->all()));
    }
}
