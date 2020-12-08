<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'users/create';

    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    private array $userData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->userData = UserFactory::createRandomRegistrationModelData();
    }

    public function test_create_as_admin(): void
    {
        $this->assertDatabaseMissing('users', $this->userData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->userData);
        unset($this->userData['password']);

        $response->assertOk();
        $this->assertDatabaseHas('users', $this->userData);

        $responseData = $response->json('res');
        unset($responseData['online']);
        $this->assertDatabaseHas('users', $responseData);
    }

    public function test_create_as_manager(): void
    {
        $this->assertDatabaseMissing('users', $this->userData);

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->userData);

        $response->assertForbidden();
    }

    public function test_create_as_auditor(): void
    {
        $this->assertDatabaseMissing('users', $this->userData);

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->userData);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $this->assertDatabaseMissing('users', $this->userData);

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->userData);

        $response->assertForbidden();
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
