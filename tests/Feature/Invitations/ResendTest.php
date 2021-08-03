<?php

namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\Invitation;
use Tests\Facades\UserFactory;
use Tests\Facades\InvitationFactory;
use Tests\TestCase;

class ResendTest extends TestCase
{
    private const URI = 'invitations/resend';

    private User $admin;
    private User $manager;
    private User $auditor;
    private User $user;

    private Invitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->invitation = InvitationFactory::create();
    }

    public function test_resend_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertOk();
        $response->assertNotEquals(
            $response->decodeResponseJson()['res']['expires_at'],
            $this->invitation->expires_at->toISOString()
        );
    }

    public function test_resend_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertForbidden();
    }

    public function test_resend_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertForbidden();
    }

    public function test_resend_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertForbidden();
    }
}
