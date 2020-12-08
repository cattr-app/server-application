<?php

namespace Tests\Feature\Auth\PasswordReset;

use App\Mail\ResetPassword;
use App\Models\User;
use Notification;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RequestTest extends TestCase
{
    private const URI = 'auth/password/reset/request';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::create();
    }

    public function test_request(): void
    {
        Notification::fake();
        Notification::assertNothingSent();

        $response = $this->postJson(self::URI, ['email' => $this->user->email]);

        $response->assertOk();
        Notification::assertSentTo($this->user, ResetPassword::class);
    }

    public function test_wrong_email(): void
    {
        Notification::fake();
        Notification::assertNothingSent();

        $response = $this->postJson(self::URI, ['email' => 'wronemail@example.com']);

        $response->assertNotFound('authorization.user_not_found');
        Notification::assertNothingSent();
    }

    public function test_without_params(): void
    {
        Notification::fake();
        Notification::assertNothingSent();

        $response = $this->postJson(self::URI);

        $response->assertError(self::HTTP_BAD_REQUEST);
        Notification::assertNothingSent();
    }
}
