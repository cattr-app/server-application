<?php

namespace App\Http\Middleware;

use App\Exceptions\Entities\AuthorizationException;
use App\Models\User;
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
            /** @var User $user */
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
            $id = $request->get('id');

            // Handled on the query level
            if ($object === 'users' && in_array($action, ['list', 'show', 'edit'])
                || in_array($object, ['projects', 'tasks']) && in_array($action, ['list', 'show'])
                || in_array($object, ['screenshots', 'time-intervals']) && in_array($action, ['list', 'show', 'edit', 'remove'])) {
                return $next($request);
            }

            if ($user->allowed($object, $action, $id) || $user->allowed($object, 'full_access')) {
                return $next($request);
            } else {
                throw new AuthorizationException(
                    AuthorizationException::ERROR_TYPE_FORBIDDEN,
                    "Access denied to $object/$action"
                );
            }
        }

        throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
    }
}
