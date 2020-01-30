<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class RemoveTest
 */
class RemoveTest extends TestCase
{
    private const URI = 'v1/users/remove';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->user = UserFactory::create();
    }

    public function test_remove(): void
    {
        $this->assertDatabaseHas('users', $this->user->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->only('id'));

        $response->assertSuccess();
        $this->assertSoftDeleted('users', $this->user->only('id'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
