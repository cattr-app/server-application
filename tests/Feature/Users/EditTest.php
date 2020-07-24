<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Faker\Factory as FakerFactory;

class EditTest extends TestCase
{
    private const URI = 'users/edit';

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    public function test_edit_as_admin(): void
    {
        // TODO FIX if user has no access to edit requested user, then query will be empty and wrong error will return

        $this->user->full_name = 'New Name';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->toArray());

        $response->assertSuccess();
        $response->assertJson(['res' => $this->user->toArray()]);
        $this->assertDatabaseHas('users', $this->user->only('id', 'full_name'));
    }

    public function test_edit_as_user(): void
    {
        $faker = FakerFactory::create();
        $user = clone $this->user;

        $user->full_name = $faker->unique()->firstName;
        $user->email = $faker->unique()->email;
        $user->password = $faker->unique()->password;
        $user->user_language = 'en';

        $response = $this->actingAs($this->user)->postJson(
            self::URI,
            $user->only('id', 'full_name', 'email', 'password', 'user_language')
        );

        $response->assertSuccess();
        $response->assertJson(['res' => $user->toArray()]);
        $this->assertDatabaseHas(
            'users',
            $user->only('id', 'full_name', 'email', 'user_language')
        );
    }

    public function test_edit_forbidden_field_as_user(): void
    {
        $user = clone $this->user;
        $user->is_admin = true;

        $response = $this->actingAs($this->user)->postJson(
            self::URI,
            $user->only('id', 'is_admin')
        );

        $response->assertValidationError();
    }

    public function test_not_existing(): void
    {
        $this->user->id++;
        $this->user->email = 'newemail@example.com';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->toArray());

        $response->assertNotFound();
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
