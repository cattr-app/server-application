<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Exceptions\Entities\AuthorizationException;
use App\Http\Controllers\AuthController;
use App\Models\OidcIdentity;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\Process\Process;
use Tests\Support\OidcProvider;
use Tests\Support\OidcSharedWebTestCase;

class OidcDesktopConcurrencyTest extends OidcSharedWebTestCase
{
    private array $workers = [];

    protected function setUp(): void
    {
        $this->sharedDirectory = sys_get_temp_dir() . '/cattr-oidc-race-' . bin2hex(random_bytes(12));
        mkdir($this->sharedDirectory, 0700, true);
        touch($this->sharedDirectory . '/database.sqlite');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->workers as $worker) {
                $worker->stop(0);
            }
            if ($this->app) {
                $this->app['db']->disconnect('sqlite');
            }
            parent::tearDown();
        } finally {
            (new Filesystem())->deleteDirectory($this->sharedDirectory);
        }
    }

    private function startExchange(string $key, string $role): Process
    {
        $worker = new Process([
            PHP_BINARY, base_path('tests/Support/oidc-desktop-worker.php'), $this->sharedDirectory, $role,
        ], base_path(), ['APP_ENV' => 'testing'], json_encode(['key' => $key], JSON_THROW_ON_ERROR), 20);
        $worker->setOptions(['create_new_console' => false]);
        $this->workers[] = $worker;
        $worker->start();
        return $worker;
    }

    private function awaitSignal(string $signal, Process $worker): void
    {
        $deadline = microtime(true) + 5;
        while (!is_file($this->sharedDirectory . '/' . $signal)) {
            if (!$worker->isRunning()) {
                $this->fail('Worker exited before ' . $signal . ': ' . $worker->getErrorOutput());
            }
            if (microtime(true) >= $deadline) {
                $this->fail('Worker did not reach ' . $signal);
            }
            usleep(10000);
            clearstatcache();
        }
    }

    public function test_overlapping_desktop_exchanges_issue_exactly_one_token(): void
    {
        $user = User::create([
            'full_name' => 'Concurrent employee', 'email' => 'race@example.test',
            'password' => 'test-only-password', 'active' => true, 'role_id' => Role::USER->value,
        ]);
        OidcIdentity::create([
            'user_id' => $user->id, 'issuer' => OidcProvider::ISSUER, 'subject' => 'race-employee',
            'identity_hash' => OidcIdentity::identityHash(OidcProvider::ISSUER, 'race-employee'),
        ]);
        $source = $user->createToken('oidc:test', ['*'], now()->addHour())->accessToken;
        $request = Request::create('https://cattr.example.test/api/auth/desktop-key');
        $request->setUserResolver(static fn() => $user->withAccessToken($source));
        auth()->guard('web')->setUser($user);
        $key = $this->app->make(AuthController::class)->issueDesktopKey($request)->getData(true)['data']['access_token'];

        $first = $this->startExchange($key, 'first');
        $this->awaitSignal('first.acquired', $first);
        $second = $this->startExchange($key, 'second');
        $this->awaitSignal('second.contended', $second);
        $this->assertTrue($first->isRunning());
        $this->assertTrue($second->isRunning());
        $this->assertSame(1, PersonalAccessToken::count(), 'Neither waiting request has issued a token');
        touch($this->sharedDirectory . '/release');

        $this->assertSame(0, $first->wait(), $first->getErrorOutput());
        $this->assertSame(0, $second->wait(), $second->getErrorOutput());
        $this->assertSame(['outcome' => 'issued', 'user_id' => $user->id], json_decode($first->getOutput(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame(['outcome' => 'rejected', 'exception' => AuthorizationException::class], json_decode($second->getOutput(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame(2, PersonalAccessToken::count());
        $desktop = PersonalAccessToken::where('name', 'like', 'oidc:desktop:%')->sole();
        $this->assertSame($user->id, (int) $desktop->tokenable_id);
        $this->assertSame($user->getMorphClass(), $desktop->tokenable_type);
        $this->assertTrue($source->expires_at->equalTo($desktop->expires_at));
    }
}
