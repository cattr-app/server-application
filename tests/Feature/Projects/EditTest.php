<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;


/**
 * Class EditTest
 */
class EditTest extends TestCase
{
    private const URI = 'v1/projects/edit';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->project = ProjectFactory::create()->makeHidden('updated_at');
    }


    public function test_edit(): void
    {
        $this->project->description = 'New Description';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());

        $response->assertSuccess();
        $response->assertJson(['res' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_not_existing_project(): void
    {
        $this->project->id = 42;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());

        $response->assertItemNotFound();
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
