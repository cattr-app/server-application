<?php

namespace Tests\Feature\QueryFilters;

use App\Models\Project;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Relation filters (where[relation.field]) of the list endpoints go through QueryHelper::apply().
 */
class RelationFilterTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_filters_by_relation(): void
    {
        $task = TaskFactory::forUser($this->admin)->create();
        TaskFactory::refresh()->create();

        $response = $this->actingAs($this->admin)->postJson('tasks/list', ['where' => ['users.id' => $this->admin->id]]);

        $response->assertOk();
        $this->assertSame([$task->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_unknown_relation(): void
    {
        $response = $this->actingAs($this->admin)->postJson('task-comment/list', ['where' => ['missing.id' => 1]]);

        $response->assertValidationError();
    }

    public function test_model_method_that_is_not_a_relation(): void
    {
        $projects = Project::withoutGlobalScopes()->count();

        $response = $this->actingAs($this->admin)->postJson('projects/list', ['where' => ['save.id' => 1]]);

        $response->assertValidationError();
        $this->assertSame($projects, Project::withoutGlobalScopes()->count());
    }
}
