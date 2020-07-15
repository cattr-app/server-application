<?php

namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\invitation;
use Tests\Facades\UserFactory;
use Tests\Facades\invitationFactory;
use Tests\TestCase;

class ResendTest extends TestCase
{
    private const URI = 'invitations/resend';

    private User $user;
    private User $admin;

    private Invitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->invitation = InvitationFactory::create();
    }

    public function test_resend_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertSuccess();
        $response->assertNotEquals(
            $response->decodeResponseJson()['res']['expires_at'],
            $this->invitation->expires_at->toISOString()
        );
    }
}
