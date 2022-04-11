<?php

namespace App\Http\Middleware;

use App\Exceptions\Entities\InstallationException;
use Closure;
use Illuminate\Http\Request;
use Settings;
use Throwable;

class EnsureIsInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): mixed
    {
        throw_unless(Settings::scope('core')->get('installed'), InstallationException::class);

        return $next($request);
    }
}
