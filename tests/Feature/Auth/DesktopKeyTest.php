<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DesktopKeyTest extends TestCase
{
    private const URI = 'auth/desktop-key';

    public static function roles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    private function user(string $role = 'asUser'): User
    {
        return UserFactory::refresh()->$role()->withTokens()->create();
    }

    private function issueKey(User $user): string
    {
        $key = $this->actingAs($user)->getJson(self::URI)->assertOk()->json('data.access_token');

        // auth:sanctum switches the default guard for the rest of the test, the exchange is a separate request
        auth()->shouldUse('web');

        return $key;
    }

    private function cacheKey(string $key): string
    {
        return sha1('127.0.0.1') . ":$key";
    }

    public function test_guest_cannot_issue_a_key(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }

    #[DataProvider('roles')]
    public function test_every_role_can_issue_a_key(string $role): void
    {
        $user = $this->user($role);

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'desktop')
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);

        $key = $response->json('data.access_token');
        $this->assertSame(40, strlen($key));
        $this->assertSame($user->id, Cache::store('octane')->get($this->cacheKey($key)));
    }

    public function test_every_issued_key_is_unique(): void
    {
        $user = $this->user();

        $this->assertNotSame($this->issueKey($user), $this->issueKey($user));
    }

    public function test_the_key_is_exchanged_for_a_working_bearer_token(): void
    {
        $user = $this->user('asManager');
        $key = $this->issueKey($user);
        $tokensBefore = PersonalAccessToken::where('tokenable_id', $user->id)->count();

        $response = $this->putJson(self::URI, [], ['Authorization' => "desktop $key"]);

        $response->assertOk()->assertJsonStructure(['data' => ['access_token', 'token_type']]);
        $this->assertSame($tokensBefore + 1, PersonalAccessToken::where('tokenable_id', $user->id)->count());

        $plainToken = $response->json('data.access_token');
        $this->assertSame($user->id, PersonalAccessToken::findToken($plainToken)?->tokenable_id);

        // The guards keep the user of the previous requests: drop them, so that the token itself is checked
        Auth::forgetGuards();

        $this->withHeader('Authorization', "Bearer $plainToken")
            ->getJson('auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_a_missing_authorization_header_is_unauthorized(): void
    {
        $this->putJson(self::URI)->assertUnauthorized();
    }

    public function test_an_unknown_key_is_unauthorized(): void
    {
        $this->putJson(self::URI, [], ['Authorization' => 'desktop ' . str_repeat('a', 40)])->assertUnauthorized();
    }

    public function test_a_key_issued_for_another_address_is_unauthorized(): void
    {
        $key = $this->issueKey($this->user());

        $this->putJson(self::URI, [], ['Authorization' => "desktop $key"])->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->putJson(self::URI, [], ['Authorization' => "desktop $key"])
            ->assertUnauthorized();
    }

    public function test_an_expired_key_is_unauthorized(): void
    {
        $key = $this->issueKey($this->user());

        $this->travel(config('auth.lifetime_minutes.desktop_token') + 1)->minutes();

        $this->putJson(self::URI, [], ['Authorization' => "desktop $key"])->assertUnauthorized();
    }

    #[DataProvider('malformedHeaders')]
    public function test_a_malformed_authorization_header_is_unauthorized(string $scheme, bool $withKey): void
    {
        $key = $this->issueKey($this->user());

        $this->putJson(self::URI, [], ['Authorization' => $withKey ? "$scheme $key" : $scheme])
            ->assertUnauthorized();
    }

    public static function malformedHeaders(): array
    {
        return [
            'bearer scheme' => ['Bearer', true],
            'no scheme' => ['', true],
            'scheme only' => ['desktop', false],
        ];
    }

    public function test_a_disabled_user_cannot_exchange_the_key(): void
    {
        $user = $this->user();
        $key = $this->issueKey($user);
        $user->active = false;
        $user->save();
        $tokensBefore = PersonalAccessToken::where('tokenable_id', $user->id)->count();

        $this->putJson(self::URI, [], ['Authorization' => "desktop $key"])
            ->assertForbidden('authorization.user_disabled');

        $this->assertSame($tokensBefore, PersonalAccessToken::where('tokenable_id', $user->id)->count());
    }
}
