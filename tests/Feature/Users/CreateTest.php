<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;


class CreateTest extends TestCase
{
    private const URI = 'v1/users/create';

    private User $admin;

    private array $userData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->userData = UserFactory::createRandomRegistrationModelData();
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('users', $this->userData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->userData);
        unset($this->userData['password']);

        $response->assertSuccess();
        $this->assertDatabaseHas('users', $this->userData);
        $this->assertDatabaseHas('users', $response->json('res'));
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
