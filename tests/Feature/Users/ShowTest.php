<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;



class ShowTest extends TestCase
{
    private const URI = 'v1/users/show';

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->user = UserFactory::create();
    }

    public function test_show(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->only('id'));

        $response->assertOk();
        $response->assertJson($this->user->toArray());
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
