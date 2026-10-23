<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Http\Controllers\AuthController;
use App\Models\OidcIdentity;
use App\Models\User;
use Illuminate\Http\Client\Factory;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\OidcProvider;
use Tests\Support\OidcWebTestCase;

class OidcTest extends OidcWebTestCase
{
    private function user(int $role = Role::USER->value): User
    {
        return User::create([
            'full_name' => 'Test employee', 'email' => 'employee@example.test',
            'password' => 'test-only-password', 'active' => true, 'role_id' => $role,
        ]);
    }

    private function link(User $user, string $subject = 'employee-123'): OidcIdentity
    {
        return OidcIdentity::create([
            'user_id' => $user->id, 'issuer' => OidcProvider::ISSUER, 'subject' => $subject,
            'identity_hash' => OidcIdentity::identityHash(OidcProvider::ISSUER, $subject),
        ]);
    }

    private function provider(): OidcProvider
    {
        $this->app['session']->start();
        $factory = new Factory();
        $this->app->instance(Factory::class, $factory);
        return new OidcProvider($factory, $this->app['session']->driver());
    }

    private function authorize(): void
    {
        $response = $this->get('https://cattr.example.test/auth/oidc/redirect');
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->get('https://cattr.example.test/auth/oidc/callback?' . http_build_query([
            'code' => 'test-code', 'state' => $query['state'],
        ]))->assertRedirect('/auth/oidc/complete');
    }

    private function exchange(array $data = [])
    {
        return $this->postJson('https://cattr.example.test/auth/oidc/token', array_merge([
            '_token' => $this->app['session']->token(),
        ], $data));
    }

    #[DataProvider('roles')]
    public function test_existing_employee_and_admin_keep_their_own_roles(int $role): void
    {
        $user = $this->user($role);
        $this->link($user);
        $this->provider();
        $this->authorize();
        $response = $this->exchange()->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($user->id, $response->json('data.user.id'));
        $this->assertSame($role, $response->json('data.user.role_id'));
        $token = PersonalAccessToken::findToken($response->json('data.access_token'));
        $this->assertSame($user->id, (int) $token->tokenable_id);
        $this->assertNotNull($token->expires_at);
        $this->assertSame($role, $user->fresh()->role_id);
        $this->exchange()->assertUnauthorized();
        $this->assertSame(1, PersonalAccessToken::count());
    }

    public static function roles(): array
    {
        return ['employee' => [Role::USER->value], 'administrator' => [Role::ADMIN->value]];
    }

    public function test_matching_email_never_automatically_links_or_creates_an_account(): void
    {
        $this->user();
        $this->provider();
        $response = $this->get('https://cattr.example.test/auth/oidc/redirect');
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $p);
        $this->get('https://cattr.example.test/auth/oidc/callback?' . http_build_query([
            'code' => 'test-code', 'state' => $p['state'],
        ]))->assertRedirect('/auth/login?sso_error=1');
        $this->assertSame(1, User::count());
        $this->assertSame(0, OidcIdentity::count());
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_token_exchange_requires_csrf_even_with_a_valid_browser_grant(): void
    {
        $this->link($this->user());
        $this->provider();
        $this->authorize();
        $this->postJson('https://cattr.example.test/auth/oidc/token')->assertStatus(419);
        $this->assertSame(0, PersonalAccessToken::count());
        $this->exchange()->assertOk();
    }

    public function test_encrypted_xsrf_cookie_header_used_by_axios_is_accepted(): void
    {
        $this->link($this->user());
        $this->provider();
        $this->authorize();
        $response = $this->get('https://cattr.example.test/auth/oidc/config');
        $cookie = $response->getCookie('XSRF-TOKEN', false);
        $this->assertNotNull($cookie);
        $this->postJson('https://cattr.example.test/auth/oidc/token', [], [
            'X-XSRF-TOKEN' => $cookie->getValue(),
        ])->assertOk();
    }

    public function test_csrf_failure_preserves_419_with_cattrs_production_exception_handler(): void
    {
        $this->app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class,
            new \App\Exceptions\Handler($this->app));
        $this->link($this->user());
        $this->provider();
        $this->authorize();
        $this->postJson('https://cattr.example.test/auth/oidc/token')
            ->assertStatus(419)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, PersonalAccessToken::count());
        $this->exchange()->assertOk();
    }

    public function test_inactive_account_is_denied_during_callback(): void
    {
        $user = $this->user();
        $this->link($user);
        $user->update(['active' => false]);
        $this->provider();
        $response = $this->get('https://cattr.example.test/auth/oidc/redirect');
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $p);
        $this->get('https://cattr.example.test/auth/oidc/callback?' . http_build_query([
            'code' => 'test-code', 'state' => $p['state'],
        ]))->assertRedirect('/auth/login?sso_error=1');
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_callback_and_token_cannot_be_used_from_a_different_session(): void
    {
        $this->link($this->user());
        $this->provider();
        $this->authorize();
        $this->flushSession();
        $this->app['session']->regenerate(true);
        $this->exchange()->assertUnauthorized();
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_expired_grant_does_not_issue_a_token(): void
    {
        $this->link($this->user());
        $this->provider();
        $this->authorize();
        $this->app['session']->put('oidc_grant.expires', time() - 1);
        $this->exchange()->assertUnauthorized();
    }

    #[DataProvider('accountChanges')]
    public function test_account_must_still_be_eligible_at_token_exchange(string $change): void
    {
        $user = $this->user();
        $identity = $this->link($user);
        $this->provider();
        $this->authorize();
        if ($change === 'disabled') {
            $user->update(['active' => false]);
        } elseif ($change === 'deleted') {
            $user->delete();
        } else {
            $identity->delete();
        }
        $this->exchange()->assertUnauthorized();
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public static function accountChanges(): array
    {
        return [['disabled'], ['deleted'], ['unlinked']];
    }

    public function test_disabled_feature_has_no_authentication_entry_points(): void
    {
        config(['oidc.enabled' => false]);
        $this->getJson('https://cattr.example.test/auth/oidc/config')->assertExactJson(['enabled' => false]);
        $this->get('https://cattr.example.test/auth/oidc/redirect')->assertNotFound();
        $this->get('https://cattr.example.test/auth/oidc/callback?code=test-code')->assertNotFound();
        $this->app['session']->start();
        $this->exchange()->assertNotFound();
    }

    public function test_errors_do_not_echo_provider_secrets_or_arbitrary_redirects(): void
    {
        $this->get('https://cattr.example.test/auth/oidc/callback?error=provider-secret&error_description=private-code&return_to=https://other.example.test')
            ->assertRedirect('/auth/login?sso_error=1')->assertDontSee('provider-secret')->assertDontSee('private-code');
    }

    public function test_identities_are_case_sensitive_and_binding_cannot_be_stolen(): void
    {
        $user = $this->user();
        $this->link($user);
        $this->assertNotSame(OidcIdentity::identityHash(OidcProvider::ISSUER, 'employee-123'), OidcIdentity::identityHash(OidcProvider::ISSUER, 'Employee-123'));
        $this->artisan('cattr:oidc:link', ['user_id' => $user->id, 'subject' => 'another-subject'])->assertFailed();
        $this->assertSame('employee-123', OidcIdentity::first()->subject);
    }

    public function test_operator_binding_is_idempotent_and_preserves_password_and_role(): void
    {
        $user = $this->user(Role::ADMIN->value);
        $password = $user->password;
        for ($i = 0; $i < 2; $i++) {
            $this->artisan('cattr:oidc:link', ['user_id' => $user->id, 'subject' => 'employee-123'])->assertSuccessful();
        }
        $this->assertSame(1, OidcIdentity::count());
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame(Role::ADMIN->value, $user->fresh()->role_id);
    }

    public function test_operator_unlink_revokes_tokens_and_outstanding_grants(): void
    {
        $user = $this->user();
        $this->link($user);
        $this->provider();
        $this->authorize();
        $user->createToken('existing-device');
        $this->artisan('cattr:oidc:link', ['user_id' => $user->id, '--unlink' => true])->assertSuccessful();
        $this->assertSame(0, OidcIdentity::count());
        $this->assertSame(0, PersonalAccessToken::count());
        $this->exchange()->assertUnauthorized();
    }

    public function test_operator_can_unlink_an_already_disabled_or_deleted_account(): void
    {
        $user = $this->user();
        $this->link($user);
        $user->createToken('existing-device');
        $user->update(['active' => false]);
        $user->delete();
        config(['oidc.issuer' => null, 'oidc.enabled' => false]);
        $this->artisan('cattr:oidc:link', ['user_id' => $user->id, '--unlink' => true])->assertSuccessful();
        $this->assertSame(0, OidcIdentity::count());
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_migration_rollback_preserves_users_and_tokens(): void
    {
        $user = $this->user();
        $this->link($user);
        $user->createToken('existing-device');
        $migration = require $this->app->basePath('database/migrations/2026_10_23_120000_create_oidc_identities_table.php');
        $migration->down();
        $this->assertSame(1, User::count());
        $this->assertSame(1, PersonalAccessToken::count());
    }

    public function test_mutating_authentication_routes_use_session_locks(): void
    {
        foreach (['redirect' => 'GET', 'callback' => 'GET', 'token' => 'POST'] as $path => $method) {
            $request = \Illuminate\Http\Request::create('/auth/oidc/' . $path, $method);
            $route = $this->app['router']->getRoutes()->match($request);
            $this->assertSame(90, $route->locksFor());
        }
    }

    private function desktopKey(User $user): string
    {
        $request = \Illuminate\Http\Request::create('https://cattr.example.test/api/auth/desktop-key');
        $request->setUserResolver(static fn() => $user);
        auth()->guard('web')->setUser($user);
        return $this->app->make(AuthController::class)->issueDesktopKey($request)->getData(true)['data']['access_token'];
    }

    private function desktopExchange(string $key)
    {
        $request = \Illuminate\Http\Request::create('https://cattr.example.test/api/auth/desktop-key', 'PUT');
        $request->headers->set('Authorization', 'desktop ' . $key);
        return $this->app->make(AuthController::class)->authDesktopKey($request);
    }

    public function test_desktop_handoff_preserves_sso_identity_expiry_and_is_one_use(): void
    {
        $user = $this->user();
        $this->link($user);
        $this->provider();
        $this->authorize();
        $web = $this->exchange()->assertOk();
        $source = PersonalAccessToken::findToken($web->json('data.access_token'));
        $key = $this->desktopKey($user->withAccessToken($source));
        $data = $this->desktopExchange($key)->getData(true)['data'];
        $desktop = PersonalAccessToken::findToken($data['access_token']);
        $this->assertSame($user->id, $data['user']['id']);
        $this->assertTrue($source->expires_at->equalTo($desktop->expires_at));
        $this->expectException(\App\Exceptions\Entities\AuthorizationException::class);
        $this->desktopExchange($key);
    }

    public function test_unlink_invalidates_pending_desktop_handoff(): void
    {
        $user = $this->user();
        $identity = $this->link($user);
        $source = $user->createToken('oidc:test', ['*'], now()->addHour())->accessToken;
        $key = $this->desktopKey($user->withAccessToken($source));
        $identity->delete();
        $this->expectException(\App\Exceptions\Entities\AuthorizationException::class);
        $this->desktopExchange($key);
    }

    #[DataProvider('tokenRevocations')]
    public function test_revoked_source_cannot_exchange_a_pending_desktop_grant(bool $allTokens): void
    {
        $user = $this->user();
        $this->link($user);
        $source = $user->createToken('oidc:test', ['*'], now()->addHour())->accessToken;
        $key = $this->desktopKey($user->withAccessToken($source));
        $allTokens ? $user->tokens()->delete() : $source->delete();
        $this->expectException(\App\Exceptions\Entities\AuthorizationException::class);
        $this->desktopExchange($key);
    }

    public static function tokenRevocations(): array
    {
        return ['source revoked' => [false], 'all sessions revoked' => [true]];
    }

    public function test_pending_desktop_grant_respects_a_shortened_source_expiry(): void
    {
        $user = $this->user();
        $this->link($user);
        $source = $user->createToken('oidc:test', ['*'], now()->addHour())->accessToken;
        $key = $this->desktopKey($user->withAccessToken($source));
        $source->forceFill(['expires_at' => now()->addMinutes(5)])->save();
        $data = $this->desktopExchange($key)->getData(true)['data'];
        $desktop = PersonalAccessToken::findToken($data['access_token']);
        $this->assertNotNull($desktop);
        $this->assertSame($user->id, $desktop->tokenable_id);
        $this->assertTrue($source->expires_at->equalTo($desktop->expires_at));
    }

    public function test_callback_failure_records_only_a_fixed_label_and_exception_class(): void
    {
        $sink = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $sink->shouldReceive('warning')->once()->with('oidc.callback_failed', [
            'exception_class' => \RuntimeException::class,
        ]);
        \Illuminate\Support\Facades\Log::shouldReceive('channel')->once()->with('stderr')->andReturn($sink);
        $this->get('https://cattr.example.test/auth/oidc/callback?code=sensitive-test-code&state=invalid')
            ->assertRedirect('/auth/login?sso_error=1');
    }

    public function test_unavailable_diagnostic_sink_does_not_change_the_callback_failure_response(): void
    {
        \Illuminate\Support\Facades\Log::shouldReceive('channel')->once()->with('stderr')
            ->andThrow(new \RuntimeException('Private logger detail'));
        $this->get('https://cattr.example.test/auth/oidc/callback?code=sensitive-test-code&state=invalid')
            ->assertRedirect('/auth/login?sso_error=1');
    }

    public function test_start_failure_records_only_a_fixed_label_and_exception_class(): void
    {
        $this->app['config']->set('oidc.client_secret', null);
        $sink = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $sink->shouldReceive('warning')->once()->with('oidc.authorization_start_failed', [
            'exception_class' => \RuntimeException::class,
        ]);
        \Illuminate\Support\Facades\Log::shouldReceive('channel')->once()->with('stderr')->andReturn($sink);
        $this->get('https://cattr.example.test/auth/oidc/redirect')->assertStatus(503);
    }

    public function test_existing_password_desktop_protocol_is_unchanged(): void
    {
        $user = $this->user();
        $source = $user->createToken('existing-password-session')->accessToken;
        $key = $this->desktopKey($user->withAccessToken($source));
        $data = $this->desktopExchange($key)->getData(true)['data'];
        $this->assertSame($user->id, $data['user']['id']);
        $this->assertNull(PersonalAccessToken::findToken($data['access_token'])->expires_at);
    }

    public function test_desktop_handoff_fits_the_native_octane_table_key_limit(): void
    {
        $this->app['cache']->extend('bounded-test', fn ($app) => $app['cache']->repository(
            new class extends \Illuminate\Cache\ArrayStore {
                public function put($key, $value, $seconds)
                {
                    if (strlen($key) > 63) {
                        throw new \LogicException('Native Octane table key exceeds 63 bytes');
                    }
                    return parent::put($key, $value, $seconds);
                }
            },
        ));
        $this->app['config']->set('cache.stores.octane', ['driver' => 'bounded-test']);
        $this->app['cache']->forgetDriver('octane');
        $user = $this->user();
        $this->link($user);
        $source = $user->createToken('oidc:test', ['*'], now()->addHour())->accessToken;
        $key = $this->desktopKey($user->withAccessToken($source));
        $data = $this->desktopExchange($key)->getData(true)['data'];
        $this->assertSame($user->id, $data['user']['id']);
        $this->assertTrue($source->expires_at->equalTo(
            PersonalAccessToken::findToken($data['access_token'])->expires_at,
        ));
    }

    public function test_first_party_session_desktop_protocol_is_unchanged(): void
    {
        $user = $this->user()->withAccessToken(new \Laravel\Sanctum\TransientToken());
        $key = $this->desktopKey($user);
        $data = $this->desktopExchange($key)->getData(true)['data'];
        $this->assertSame($user->id, $data['user']['id']);
    }
}
