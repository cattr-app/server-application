<?php

namespace Tests\Feature\Invitations;

use App\Models\invitation;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'v1/invitations/list';

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $invitations = invitation::all()->toArray();

        $response->assertJson($invitations);
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertForbidden();
    }
}
