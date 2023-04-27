<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Route;
use Sentry\State\Scope;
use Throwable;
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
                    'email' => config('sentry.send_default_pii') ? $user->email : sha1($user->email),
                    'role' => is_int($user->role_id) ? Role::tryFrom($user->role_id)?->name :$user->role_id->name,
                ]);
            });
        }

        configureScope(static function (Scope $scope) use ($request): void {
            $scope->setTag('request.host', $request->host());
            $scope->setTag('request.method', $request->method());
            try {
                $scope->setTag('request.route', Route::getRoutes()->match($request)->getName());
            } catch (Throwable) {
            }
        });

        return $next($request);
    }
}
