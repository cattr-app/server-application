<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Settings;

class EnsureIsInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Settings::scope('core')->get('installed'), 400, 'You need to run installation');

        return $next($request);
    }
}
