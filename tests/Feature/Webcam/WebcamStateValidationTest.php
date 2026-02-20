<?php

namespace Tests\Feature\Webcam;

use App\Enums\WebcamState;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class WebcamStateValidationTest extends TestCase
{

    private User $admin;
    private User $user;

    protected function setUp(): void
    {

        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

    }

    public function test_company_settings_accepts_valid_webcam_state(): void
    {

        $response = $this->actingAs($this->admin)->patchJson('company-settings', [
            'webcam_state' => WebcamState::REQUIRED->value,
        ]);

        $response->assertOk();

    }

    public function test_company_settings_rejects_invalid_webcam_state(): void
    {

        $response = $this->actingAs($this->admin)->patchJson('company-settings', [
            'webcam_state' => 999,
        ]);

        $response->assertValidationError();

    }

    public function test_company_settings_non_admin_forbidden(): void
    {

        $response = $this->actingAs($this->user)->patchJson('company-settings', [
            'webcam_state' => WebcamState::OPTIONAL->value,
        ]);

        $response->assertForbidden();

    }

    public function test_create_user_with_valid_webcam_state(): void
    {

        $userData = UserFactory::createRandomRegistrationModelData();
        $userData['webcam_state'] = WebcamState::REQUIRED->value;

        $response = $this->actingAs($this->admin)->postJson('users/create', $userData);

        $response->assertOk();

    }

    public function test_create_user_with_invalid_webcam_state(): void
    {

        $userData = UserFactory::createRandomRegistrationModelData();
        $userData['webcam_state'] = 999;

        $response = $this->actingAs($this->admin)->postJson('users/create', $userData);

        $response->assertValidationError();

    }

    public function test_edit_user_with_valid_webcam_state(): void
    {

        $targetUser = UserFactory::refresh()->asUser()->withTokens()->create();

        $response = $this->actingAs($this->admin)->postJson('users/edit', [
            'id' => $targetUser->id,
            'webcam_state' => WebcamState::FORBIDDEN->value,
        ]);

        $response->assertOk();

    }

    public function test_create_project_with_valid_webcam_state(): void
    {

        $projectData = ProjectFactory::createRandomModelData();
        $projectData['screenshots_state'] = 1;
        $projectData['webcam_state'] = WebcamState::OPTIONAL->value;

        $response = $this->actingAs($this->admin)->postJson('projects/create', $projectData);

        $response->assertOk();

    }

    public function test_create_project_with_invalid_webcam_state(): void
    {

        $projectData = ProjectFactory::createRandomModelData();
        $projectData['screenshots_state'] = 1;
        $projectData['webcam_state'] = 999;

        $response = $this->actingAs($this->admin)->postJson('projects/create', $projectData);

        $response->assertValidationError();

    }

    public function test_edit_project_with_valid_webcam_state(): void
    {

        $project = ProjectFactory::create();

        $response = $this->actingAs($this->admin)->postJson('projects/edit', [
            'id' => $project->id,
            'webcam_state' => WebcamState::REQUIRED->value,
        ]);

        $response->assertOk();

    }

}
