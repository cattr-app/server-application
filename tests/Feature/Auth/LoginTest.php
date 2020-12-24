<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Cache;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class LoginTest extends TestCase
{
    private const URI = 'auth/login';
    private const TEST_URI = 'auth/me';

    private User $user;

    private array $loginData;

    private const CAPTCHA_CACHE_KEY = 'AUTH_RECAPTCHA_LIMITER_{ip}_{email}_ATTEMPTS';
    private const BAN_CACHE_KEY = 'AUTH_RATE_LIMITER_{ip}';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::create();

        $this->loginData = [
            'email' => $this->user->email,
            'password' => $this->user->full_name
        ];
    }

    public function test_success(): void
    {
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertOk();

        $this->actingAs($response->decodeResponseJson()['access_token'])->get(self::TEST_URI)->assertOk();
    }

    public function test_wrong_credentials(): void
    {
        $this->loginData['password'] = 'wrong_password';
        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertUnauthorized();
    }

    public function test_disabled_user(): void
    {
        $this->user->active = false;
        $this->user->save();
        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertForbidden('authorization.user_disabled', false);
    }

    public function test_soft_deleted_user(): void
    {
        $this->user->delete();
        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertError(self::HTTP_BAD_REQUEST);
    }

    public function test_recaptcha(): void
    {
        config(['recaptcha.enabled' => true]);
        config(['recaptcha.failed_attempts' => 1]);

        $cacheKey = str_replace(
            ['{email}', '{ip}'],
            [$this->loginData['email'], '127.0.0.1'],
            self::CAPTCHA_CACHE_KEY
        );

        $this->assertFalse(Cache::has($cacheKey));

        $this->loginData['password'] = 'wrong_password';
        $this->postJson(self::URI, $this->loginData);

        $this->assertTrue(Cache::has($cacheKey));

        $this->assertEquals(1, Cache::get($cacheKey));

        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertError(self::HTTP_TOO_MANY_REQUESTS, 'authorization.captcha');
    }

    public function test_ban(): void
    {
        config(['recaptcha.enabled' => true]);
        config(['recaptcha.rate_limiter_enabled' => true]);
        config(['recaptcha.failed_attempts' => 0]);
        config(['recaptcha.ban_attempts' => 1]);

        $cacheKey = str_replace('{ip}', '127.0.0.1', self::BAN_CACHE_KEY);

        $this->assertFalse(Cache::has($cacheKey));

        $this->loginData['password'] = 'wrong_password';
        $this->postJson(self::URI, $this->loginData);

        $this->assertTrue(Cache::has($cacheKey));

        $cacheResponse = Cache::get($cacheKey);

        $this->assertArrayHasKey('amounts', $cacheResponse);
        $this->assertArrayHasKey('time', $cacheResponse);

        $this->assertEquals(1, $cacheResponse['amounts']);

        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertError(self::HTTP_LOCKED, 'authorization.banned');
    }
}
