<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RegisterModulesEvents;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        apiPrefix: 'api',
        then: static function (): void {
            Route::prefix('actuator')->name('actuator.')->group(base_path('routes/actuator.php'));
        },
    )
    ->withBroadcasting(__DIR__ . '/../routes/channels.php', ['middleware' => ['auth:sanctum']])
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->use([
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
            HandleCors::class,
            TrustProxies::class,
            RegisterModulesEvents::class,
        ]);

        $middleware->group('web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            SubstituteBindings::class,
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // Octane is only reached through the bundled Caddy (runtime/caddy/Caddyfile, upstream 127.0.0.1:8090),
        // which sets X-Forwarded-* and passes on incoming ones only from its own trusted_proxies. Trust exactly
        // that hop, so request()->ip() (rate limiters, recaptcha, desktop keys) is the client, not 127.0.0.1.
        $middleware->trustProxies(at: ['127.0.0.1']);

        $middleware->trimStrings(except: [
            'password',
            'password_confirmation',
        ]);

        // The pre-11 kernel had no guest redirect; keep unauthenticated requests without a redirect target.
        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions()
    ->withSingletons([
        ExceptionHandler::class => App\Exceptions\Handler::class,
    ])
    ->create();
