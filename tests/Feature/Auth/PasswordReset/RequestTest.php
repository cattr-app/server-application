<?php

namespace Tests\Feature\Auth\PasswordReset;

use App\Mail\ResetPassword;
use Tests\Facades\UserFactory;
use App\User;
use Notification;
use Tests\TestCase;

/**
 * Class RequestTest
 * @package Tests\Feature\Auth\PasswordReset
 */
class RequestTest extends TestCase
{
    private const URI = 'auth/password/reset/request';
    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::create();
    }

    public function test_request()
    {
        Notification::fake();
        Notification::assertNothingSent();

        $response = $this->postJson(self::URI, ['email' => $this->user->email]);

        $response->assertSuccess();
        Notification::assertSentTo($this->user, ResetPassword::class);
    }

    public function test_wrong_email()
    {
        Notification::fake();
        Notification::assertNothingSent();

        $response = $this->postJson(self::URI, ['email' => 'wronemail@example.com']);

        $response->assertError(404);
        Notification::assertNothingSent();
    }

    public function test_without_params()
    {
        Notification::fake();
        Notification::assertNothingSent();

        $response = $this->postJson(self::URI);

        $response->assertError(400);
        Notification::assertNothingSent();
    }
}
