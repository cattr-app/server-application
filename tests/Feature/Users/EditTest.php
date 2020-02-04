<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;


/**
 * Class EditTest
 */
class EditTest extends TestCase
{
    private const URI = 'v1/users/edit';

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

    public function test_edit(): void
    {
        //TODO FIX if user has no access to editing requested user,
        // then query will be empty and wrong error will return

        $this->user->full_name = 'New Name';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->toArray());

        $response->assertSuccess();
        $response->assertJson(['res' => $this->user->toArray()]);
        $this->assertDatabaseHas('users', $this->user->only('id', 'full_name'));
    }


    public function test_not_existing(): void
    {
        $this->user->id++;
        $this->user->email = 'newemail@example.com';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->toArray());

        $response->assertItemNotFound();
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
