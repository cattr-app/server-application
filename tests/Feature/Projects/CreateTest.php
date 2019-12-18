<?php

namespace Tests\Feature\Projects;

use App\User;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * Class CreateTest
 * @package Tests\Feature\Projects
 */
class CreateTest extends TestCase
{

    private const URI = 'v1/projects/create';

    private const COMPANY_ID = 2;

    /**
     * @var User
     */
    private $admin;
    /**
     * @var array
     */
    private $projectData;

    protected function setUp()
    {
        parent::setUp();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();

        $this->projectData = [
            'company_id'=> self::COMPANY_ID,
            'name' => 'Test Project',
            'description' => 'Test Description'
        ];
    }

    public function test_create()
    {
        $this->assertDatabaseMissing('projects', $this->projectData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->projectData);

        $response->assertApiSuccess();
        $this->assertDatabaseHas('projects', $this->projectData);
        $this->assertDatabaseHas('projects', $response->json('res'));
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertApiError(401);
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertApiError(400, true);
    }
}
