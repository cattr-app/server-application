<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Route as RouteModel;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public static function getControllerRules(): array
    {
        return [];
    }

    public function frontendRoute(Request $request) {
        return view('app');
    }

    /**
     * Laravel router pass to fallback not non-exist urls only but wrong-method requests too.
     * So required to check if route have alternative request methods
     * throw not-found or wrong-method exceptions manually
     * @param Request $request
     */
    public function universalRoute(Request $request): void
    {
        /** @var Router $router */
        $router = app('router');
        /** @var RouteCollection $routes */
        $routeCollection = $router->getRoutes();
        /** @var string[] $methods */
        $methods = array_diff(Router::$verbs, [$request->getMethod(), 'OPTIONS']);

        foreach ($methods as $method) {
            // Get all routes for method without fallback routes
            /** @var Route[]|Collection $routes */
            $routes = collect($routeCollection->get($method))->filter(static function ($route) {
                /** @var RouteModel $route */
                return !$route->isFallback && $route->uri !== '{fallbackPlaceholder}';
            });

            // Look if any route have match with current request
            $mismatch = $routes->first(static function ($value) use ($request) {
                /** @var RouteModel $value */
                return $value->matches($request, false);
            });

            // Throw wrong-method exception if matches found
            if ($mismatch !== null) {
                throw new MethodNotAllowedHttpException([]);
            }
        }

        // No matches, throw not-found exception
        throw new NotFoundHttpException();
    }
}
