<?php

namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\invitation;
use Tests\Facades\UserFactory;
use Tests\Facades\InvitationFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'invitations/create';

    private User $admin;
    private User $manager;
    private User $auditor;
    private User $user;

    private array $invitationRequestData;
    private array $invitationModelData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->invitationRequestData = InvitationFactory::createRequestData();
        $this->invitationModelData = InvitationFactory::createRandomModelData();
    }

    public function test_create_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitationRequestData);

        $response->assertOk();

        $this->assertDatabaseHas((new Invitation)->getTable(), $this->invitationRequestData['users'][0]);

        foreach ($response->json('res') as $invitation) {
            $this->assertDatabaseHas((new Invitation)->getTable(), $invitation);
        }
    }

    public function test_create_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->invitationRequestData);

        $response->assertForbidden();
    }

    public function test_create_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->invitationRequestData);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->invitationRequestData);

        $response->assertForbidden();
    }

    public function test_create_already_exists(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitationRequestData);

        $this->assertDatabaseHas((new Invitation)->getTable(), $response->decodeResponseJson()['res'][0]);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitationRequestData);

        $response->assertValidationError();
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
