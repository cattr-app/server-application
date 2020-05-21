<?php

namespace App\Http\Middleware;

use App\Helpers\Lock\Lock;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockMiddleware
{
    protected const EXCLUDED_PATHS = ['show', 'list', 'dashboard', 'allowed-rules', 'count'];
    protected Lock $lock;

    public function __construct(Lock $lock)
    {
        $this->lock = $lock;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request instanceof Request && !$request->isMethod('get') && $this->lock->isLocked()) {
            $pathElements = explode('/', $request->path());
            foreach ($pathElements as $pathElement) {
                if (in_array($pathElement, static::EXCLUDED_PATHS, true)) {
                    return $next($request);
                }
            }
            return new JsonResponse(['status' => 'Payment Required'], 402);
        }

        return $next($request);
    }
}
