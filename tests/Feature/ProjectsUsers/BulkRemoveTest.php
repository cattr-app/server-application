<?php


namespace Tests\Feature\ProjectsUsers;

use App\Models\User;
use Illuminate\Support\Collection;
use Tests\Facades\ProjectUserFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;


class BulkRemoveTest extends TestCase
{
    private const URI = 'v1/projects-users/bulk-remove';

    private const PROJECTS_USERS_AMOUNT = 5;

    private User $admin;
    private array $requestData;
    private Collection $projectsUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->projectsUsers = ProjectUserFactory::createMany(self::PROJECTS_USERS_AMOUNT);

        $this->requestData = ['relations' => $this->projectsUsers->map->only(['project_id', 'user_id'])->toArray()];
    }

    public function test_bulk_remove(): void
    {
        foreach ($this->requestData['relations'] as $relation) {
            $this->assertDatabaseHas('projects_users', $relation);
        }

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertSuccess();
        $response->assertJson(['removed' => $this->requestData['relations']]);

        $response->assertJsonMissing(['not_found']);

        foreach ($this->requestData['relations'] as $relation) {
            $this->assertDatabaseMissing('projects_users', $relation);
        }
    }

    public function test_with_not_existing_intervals(): void
    {
        foreach ($this->requestData['relations'] as $relation) {
            $this->assertDatabaseHas('projects_users', $relation);
        }

        $notRelations = [
            ['user_id' => User::max('id') + 1, 'project_id' => 1],
            ['user_id' => User::max('id') + 2, 'project_id' => 2]
        ];

        $this->requestData['relations'] = array_merge($this->requestData['relations'], $notRelations);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertStatus(self::HTTP_MULTI_STATUS);
        $response->assertJson(['not_found' => $notRelations]);

        foreach ($this->requestData['relations'] as $relation) {
            $this->assertDatabaseMissing('projects_users', $relation);
        }

        //TODO change later
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
