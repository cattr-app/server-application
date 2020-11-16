<?php

namespace Tests\Feature\Invitations;

use App\Models\Invitation;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'invitations/count';

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->asUser()->create();
        $this->admin = UserFactory::withTokens()->asAdmin()->create();
    }

    public function test_count_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['total' => Invitation::count()]);
    }

    public function test_count_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
