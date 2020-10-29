<?php

namespace Tests\Feature\Invitations;

use App\Models\invitation;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'invitations/list';

    private User $admin;
    private User $manager;
    private User $auditor;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $invitations = invitation::all()->toArray();

        $response->assertJson($invitations);
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(self::URI);

        $response->assertForbidden();
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(self::URI);

        $response->assertForbidden();
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertForbidden();
    }
}
