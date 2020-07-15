<?php
namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\Invitation;
use Tests\Facades\InvitationFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'invitations/show';

    private User $user;
    private User $admin;
    private Invitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->asUser()->create();
        $this->admin = UserFactory::withTokens()->asAdmin()->create();

        $this->invitation = InvitationFactory::create();
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertOk();
        $response->assertJson($this->invitation->toArray());
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }
}
