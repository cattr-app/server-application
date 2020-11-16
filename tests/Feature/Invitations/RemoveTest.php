<?php

namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\invitation;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Facades\UserFactory;
use Tests\Facades\InvitationFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    use WithFaker;

    private const URI = 'invitations/remove';

    private User $admin;
    private User $manager;
    private User $auditor;
    private User $user;

    private invitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->invitation = InvitationFactory::create();
    }

    public function test_remove_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertOk();
        $this->assertDeleted((new Invitation)->getTable(), $this->invitation->only('id'));
    }

    public function test_remove_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertForbidden();
    }

    public function test_not_existing(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->faker->randomNumber()]);

        $response->assertValidationError();
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
