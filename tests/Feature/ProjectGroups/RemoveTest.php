<?php

namespace Tests\Feature\ProjectGroups;

use App\Models\ProjectGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'project-groups/remove';

    private ProjectGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = ProjectGroup::create(['name' => 'Disposable']);
    }

    public static function allowedRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
        ];
    }

    public static function forbiddenRoles(): array
    {
        return [
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('allowedRoles')]
    public function test_remove(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->group->id]);

        $response->assertNoContent();
        $this->assertSoftDeleted('project_groups', ['id' => $this->group->id]);
    }

    public function test_remove_keeps_projects_of_the_group(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $project = ProjectFactory::create();
        $project->group = $this->group->id;
        $project->save();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->group->id]);

        $response->assertNoContent();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_remove_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->group->id]);

        $response->assertForbidden();
        $this->assertNotSoftDeleted('project_groups', ['id' => $this->group->id]);
    }

    public function test_removed_group_cannot_be_removed_again(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $this->group->delete();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->group->id]);

        $response->assertNotFound('page_not_found');
    }

    public function test_not_existing_group(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => ProjectGroup::withTrashed()->max('id') + 1]);

        $response->assertValidationError();
    }

    public function test_non_integer_id(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => 'abc']);

        $response->assertValidationError();
    }

    public function test_without_params(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['id' => $this->group->id]);

        $response->assertUnauthorized();
        $this->assertNotSoftDeleted('project_groups', ['id' => $this->group->id]);
    }
}
