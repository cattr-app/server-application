<?php

namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\invitation;
use Tests\Facades\UserFactory;
use Tests\Facades\InvitationFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'v1/invitations/remove';

    private User $user;
    private User $admin;

    private invitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->invitation = InvitationFactory::create();
    }

    public function test_remove_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertSuccess();
        $this->assertDeleted((new Invitation)->getTable(), $this->invitation->only('id'));
    }

    public function test_remove_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->invitation->only('id'));

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
