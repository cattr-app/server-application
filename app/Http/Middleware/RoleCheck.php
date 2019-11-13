<?php

namespace App\Http\Middleware;

use App\Exceptions\Entities\AuthorizationException;
use Closure;


class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     *
     * @return mixed
     * @throws AuthorizationException
     */
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            $actionName = $request->route()->getActionName();
            [$controller, $method] = explode('@', $actionName);

            if (method_exists($controller, 'getControllerRules')) {
                $rules = $controller::getControllerRules();

                if (isset($rules[$method])) {
                    $rule = $rules[$method];
                    [$object, $action] = explode('.', $rule);
                }
            }

            //request: /v1/{object}/{action}
            $object = isset($object) ? $object : $request->segment(2);
            $action = isset($action) ? $action : $request->segment(3);

            if ($user->allowed($object, $action) || $user->allowed($object, 'full_access')) {
                return $next($request);
            } else {
                return response()->json([
                    'error' => "Access denied to $object/$action",
                    'reason' => 'action is not allowed'
                ], 403);
            }
        }

        throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
    }
}
