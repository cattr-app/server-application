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
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (env('IMAGE_VERSION')) {
            configureScope(static function (Scope $scope): void {
                $scope->setTag('docker', env('IMAGE_VERSION'));
            });
        }

        if ($user = $request->user()) {
            configureScope(static function (Scope $scope) use ($user): void {
                $scope->setUser([
                    'id' => $user->id,
                    'name' => config('sentry.send_default_pii') ? $user->full_name : '<masked>',
                    'is_admin' => $user->is_admin,
                    'role' => $user->role->name,
                ]);
            });
        }

        return $next($request);
    }
}
