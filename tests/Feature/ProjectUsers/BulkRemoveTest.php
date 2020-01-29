<?php


namespace Tests\Feature\ProjectUsers;

use App\Models\ProjectsUsers;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CountTest
 */
class BulkRemoveTest extends TestCase
{
    private const URI = 'v1/projects-users/bulk-remove';

    private const PROJECTS_AMOUNT = 5;
    private const USERS_AMOUNT = 5;
    /**
     * @var User
     */
    private $admin;

    /**
     * @var array
     */
    private $requestData;

    /**
     * @param int $roleId
     * @return array
     */
    private function createProjectUsers(int $roleId): array
    {
        $projectIds = ProjectFactory::createMany(self::PROJECTS_AMOUNT)->pluck('id')->toArray();
        $userIds = UserFactory::createMany(self::USERS_AMOUNT)->pluck('id')->toArray();

        $relations = [];

        foreach (array_combine($userIds, $projectIds) as $userId => $projectId) {
            ProjectsUsers::create(['user_id' => $userId, 'project_id' => $projectId, 'role_id' => $roleId]);
            $relations[] = ['project_id' => $projectId, 'user_id' => $userId,];
        }

        return $relations;
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->requestData = ['relations' => $this->createProjectUsers(2)];
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
