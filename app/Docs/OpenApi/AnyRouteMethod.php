<?php

namespace App\Docs\OpenApi;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Picks the one HTTP method to document for `Route::any()` routes (Scramble would take the first one, GET).
 *
 * The method comes from the `@api {method} /path` line of the controller's apiDoc block while those exist.
 * Without one (or for `{any}`) the fallback is POST for write-like route names and GET otherwise.
 * Routes with an explicit method list are left as they are.
 */
class AnyRouteMethod
{
    private const WRITE_SUFFIXES = ['create', 'edit', 'remove', 'destroy', 'store', 'update', 'delete', 'resend', 'save'];

    /** @var array<string, string> controller file => contents */
    private array $sources = [];

    public function __invoke(Route $route): string
    {
        $methods = array_map('strtolower', $route->methods());

        if (!in_array('post', $methods, true) || !in_array('get', $methods, true) || !in_array('put', $methods, true)) {
            return $methods[0];
        }

        return $this->fromApiDoc($route) ?? $this->fallback($route);
    }

    private function fromApiDoc(Route $route): ?string
    {
        $controller = $route->getControllerClass();
        $file = $controller && class_exists($controller) ? (new ReflectionClass($controller))->getFileName() : null;
        if (!$file) {
            return null;
        }

        $this->sources[$file] ??= (string) file_get_contents($file);
        $uri = preg_quote(Str::after($route->uri, 'api/'), '#');

        if (!preg_match('#@api\s+\{([^}]+)\}\s+/(?:api/)?'.$uri.'(?:\s|$)#', $this->sources[$file], $match)) {
            return null;
        }

        foreach (preg_split('/\s*,\s*/', strtolower(trim($match[1]))) as $method) {
            if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                return $method;
            }
        }

        return null;
    }

    private function fallback(Route $route): string
    {
        return Str::endsWith((string) $route->getName(), self::WRITE_SUFFIXES) ? 'post' : 'get';
    }
}
