<?php

namespace App\Http\Middleware;

use App\Helpers\Lock\Lock;
use Closure;
use Illuminate\Http\Request;

class LockMiddleware
{
    protected $lock;
    protected const excludedPaths = ['show', 'list', 'dashboard', 'allowed-rules', 'count'];

    public function __construct(Lock $lock)
    {
        $this->lock = $lock;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request instanceof Request && !$request->isMethod('get') && $this->lock->isLocked()) {
            $pathElements = explode('/', $request->path());
            foreach ($pathElements as $pathElement) {
                if (in_array($pathElement, static::excludedPaths)) {
                    return $next($request);
                }
            }
            return response()->json(['status' => 'Payment Required'], 402);
        } else {
            return $next($request);
        }
    }
}
