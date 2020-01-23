<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CreateTest
 */
class CreateTest extends TestCase
{
    private const URI = 'v1/projects/create';

    /**
     * @var User
     */
    private $admin;
    /**
     * @var array
     */
    private $projectData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->projectData = ProjectFactory::getRandomProjectData();
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->projectData);

        $response->assertSuccess();
        $this->assertDatabaseHas('projects', $this->projectData);
        $this->assertDatabaseHas('projects', $response->json('res'));
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
