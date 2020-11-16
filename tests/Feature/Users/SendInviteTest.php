<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class SendInviteTest extends TestCase
{
    private const URI = 'users/send-invite';

    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    public function test_send_invite_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->only('id'));

        $response->assertOk();
    }

    public function test_send_invite_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_send_invite_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_send_invite_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }
}
