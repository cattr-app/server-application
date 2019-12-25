<?php

namespace Tests\Feature\Projects;

use App\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CreateTest
 * @package Tests\Feature\Projects
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

    public function test_create()
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->projectData);

        $response->assertSuccess();
        $this->assertDatabaseHas('projects', $this->projectData);
        $this->assertDatabaseHas('projects', $response->json('res'));
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
