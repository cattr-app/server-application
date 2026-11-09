<?php

namespace App\Providers;

use App\Docs\OpenApi\AnyRouteMethod;
use App\Docs\OpenApi\ErrorEnvelopeTransformer;
use App\Docs\OpenApi\ListShapedObjectTransformer;
use App\Docs\OpenApi\OperationFixes;
use App\Docs\OpenApi\ResponseEnvelopeInference;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

/**
 * OpenAPI export configuration (dedoc/scramble, a dev dependency).
 *
 * Export only: `php artisan scramble:export`. The documentation routes are never registered,
 * so nothing is exposed by the running application. No-op when Scramble is not installed.
 */
class ApiDocsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!class_exists(Scramble::class)) {
            return;
        }

        // Must run before Scramble's own provider boots.
        Scramble::ignoreDefaultRoutes();

        // Operation extensions are collected when Scramble boots, so register them here.
        Scramble::registerExtensions([
            ResponseEnvelopeInference::class,
            OperationFixes::class,
        ]);

        // Documented base URL (Scramble's default would be the local APP_URL).
        config(['scramble.servers' => ['Demo' => 'https://demo.cattr.app/api']]);

        // Bearer security on routes behind auth middleware only (login, status etc. stay public).
        config(['scramble.security_strategy' => [
            MiddlewareAuthSecurityStrategy::class,
            ['middleware' => ['auth', 'auth:*'], 'scheme' => SecurityScheme::http('bearer')->as('bearerAuth')],
        ]]);
    }

    public function boot(): void
    {
        if (!class_exists(Scramble::class)) {
            return;
        }

        Scramble::configure()
            // "api/(.*)" is the universal fallback route, not a real endpoint.
            ->resolveOperationMethodsUsing((new AnyRouteMethod)(...))
            ->routes(static fn (Route $route): bool => str_starts_with($route->uri, 'api/') && $route->getName() !== 'universal_route')
            ->withDocumentTransformers(ErrorEnvelopeTransformer::class)
            ->withDocumentTransformers(ListShapedObjectTransformer::class)
            ->withDocumentTransformers(static function (OpenApi $openApi): void {
                $openApi->info->title = 'Cattr API';
                $openApi->info->version = '4.0.0';
                $openApi->info->description = <<<'MD'
                    HTTP API of [Cattr](https://cattr.app), an open-source time tracker.

                    - [Manual](https://cattr.app/manual)
                    - [Demo](https://demo.cattr.app)
                    - [Developer documentation](https://github.com/cattr-app/server-application/blob/main/docs/developer/README.md)
                    MD;
                $openApi->info->setExtensionProperty('logo', [
                    'url' => 'logo.svg', // resolved next to the published page and openapi.json
                    'altText' => 'Cattr',
                ]);
            });
    }
}
