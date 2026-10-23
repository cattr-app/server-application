<?php

namespace Tests\Support;

use App\Console\Commands\LinkOidcIdentity;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use App\Http\Middleware\VerifyOidcCsrfToken as VerifyCsrfToken;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Schema;

/** Isolated SQLite application: never reads or migrates an operator database. */
abstract class OidcWebTestCase extends TestCase
{
    public function createApplication(): Application
    {
        $app = new Application(dirname(__DIR__, 2));
        $app->singleton(ConsoleKernel::class, \Illuminate\Foundation\Console\Kernel::class);
        $app->singleton(HttpKernel::class, OidcTestKernel::class);
        $app->singleton(ExceptionHandler::class, \Illuminate\Foundation\Exceptions\Handler::class);
        $app->instance(PackageManifest::class, new class(new \Illuminate\Filesystem\Filesystem(), $app->basePath(), '') extends PackageManifest {
            public function providers(): array { return []; }
            public function aliases(): array { return []; }
        });
        $app->beforeBootstrapping(RegisterProviders::class, static function ($app) {
            $app['config']->set([
                'app.providers' => array_merge(\Illuminate\Support\ServiceProvider::defaultProviders()->toArray(), [
                    \Flugg\Responder\ResponderServiceProvider::class,
                    \Laravel\Sanctum\SanctumServiceProvider::class,
                    \App\Providers\FilterServiceProvider::class,
                ]),
                'app.key' => 'base64:' . base64_encode(random_bytes(32)),
                'app.url' => 'https://cattr.example.test',
                'app.env' => 'testing',
                'app.debug' => false,
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
                'database.connections.sqlite.foreign_key_constraints' => true,
                'session.driver' => 'array',
                'session.block_store' => 'array',
                'session.secure' => true,
                'cache.default' => 'array',
                'cache.stores.octane' => ['driver' => 'array'],
                'oidc' => OidcProvider::configuration(),
            ]);
        });
        $app->make(ConsoleKernel::class)->bootstrap();
        $app->make(ConsoleKernel::class)->registerCommand(new LinkOidcIdentity());
        $app->bind(VerifyCsrfToken::class, static fn($app) => new class($app, $app['encrypter']) extends VerifyCsrfToken {
            protected function runningUnitTests(): bool { return false; }
        });
        // Real routes; no test-only authentication endpoints.
        $app->make(HttpKernel::class);
        $app['router']->middleware('web')->group($app->basePath('routes/web.php'));
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role_id');
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('projects_users', static function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('project_id');
            $table->integer('role_id');
        });
        Schema::create('personal_access_tokens', static function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        $migration = require $this->app->basePath('database/migrations/2026_10_23_120000_create_oidc_identities_table.php');
        $migration->up();
    }
}
