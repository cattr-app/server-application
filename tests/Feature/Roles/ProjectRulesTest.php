<?php

namespace Tests\Feature\Roles;

use App\Models\User;
use Tests\Facades\ProjectUserFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ProjectRulesTest extends TestCase
{
    private const URI = 'roles/project-rules';

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        ProjectUserFactory::forUser($this->admin)->create();
    }

    public function test_project_rules(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertSuccess();
        $this->assertNotEmpty($response->json('res'));
        // TODO: add more assertions
    }
}
