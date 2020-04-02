<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sentry\State\Scope;
use function Sentry\configureScope;

class SentryContext
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!config('sentry.send_default_pii') || auth()->check() || app()->bound('sentry')) {
            return $next($request);
        }

        $user = auth()->user();
        configureScope(static function (Scope $scope) use ($user): void {
            $scope->setUser([
                'id' => $user->id,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'role' => $user->role->name
            ]);
        });

        return $next($request);
    }
}
