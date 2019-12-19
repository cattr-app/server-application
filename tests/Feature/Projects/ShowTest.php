<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use Tests\Factories\ProjectFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;


/**
 * Class ShowTest
 * @package Tests\Feature\Projects
 */
class ShowTest extends TestCase
{
    private const URI = 'v1/projects/show';

    /**
     * @var User
     */
    private $admin;
    private $assignedUser;
    private $notAssignedUser;

    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();

        $this->assignedUser = app(UserFactory::class)
            ->withTokens()
            ->create();

        $this->notAssignedUser = app(UserFactory::class)
            ->withTokens()
            ->create();

        $this->project = app(ProjectFactory::class)->associateUsers([$this->assignedUser->id])->create();
    }

    public function test_admin()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->project->id]);

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_assigned(){
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->project->id]);

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_not_assigned(){
        $response = $this->actingAs($this->notAssignedUser)->postJson(self::URI, ['id' => $this->project->id]);

        $response->assertApiError(403, true);
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
