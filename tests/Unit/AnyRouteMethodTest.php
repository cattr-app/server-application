<?php

namespace Tests\Unit;

use App\Docs\OpenApi\AnyRouteMethod;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * The OpenAPI export documents every `Route::any()` route under one explicit HTTP method taken from
 * AnyRouteMethod::METHODS. The class has no dependency on Scramble, so this runs without dev packages too.
 */
class AnyRouteMethodTest extends TestCase
{
    /** @return list<Route> */
    private function anyRoutes(): array
    {
        return array_values(array_filter(
            RouteFacade::getRoutes()->getRoutes(),
            static fn (Route $route): bool => AnyRouteMethod::isAny($route)
                && str_starts_with($route->uri, 'api/')
                && $route->getName() !== 'universal_route',
        ));
    }

    public function test_every_any_route_is_mapped(): void
    {
        $unmapped = [];
        foreach ($this->anyRoutes() as $route) {
            if (!isset(AnyRouteMethod::METHODS[$route->getName()])) {
                $unmapped[] = $route->getName() ?? $route->uri;
            }
        }

        $this->assertNotEmpty($this->anyRoutes());
        $this->assertSame([], $unmapped, 'Add these Route::any() routes to AnyRouteMethod::METHODS.');
    }

    public function test_table_has_no_stale_entries_and_valid_methods(): void
    {
        $names = array_map(static fn (Route $route): ?string => $route->getName(), $this->anyRoutes());

        $this->assertSame([], array_values(array_diff(array_keys(AnyRouteMethod::METHODS), $names)));
        $this->assertSame([], array_values(array_diff(AnyRouteMethod::METHODS, ['get', 'post'])));
    }

    public function test_resolves_mapped_default_and_explicit_methods(): void
    {
        $resolve = new AnyRouteMethod;
        $any = static fn (string $name): Route => (new Route(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], 'api/x', []))->name($name);

        $this->assertSame('post', $resolve($any('tasks.list')));
        $this->assertSame('get', $resolve($any('tasks.count')));
        $this->assertSame('get', $resolve($any('future.unmapped')));
        $this->assertSame('put', $resolve(new Route(['PUT'], 'api/x', [])));
    }
}
