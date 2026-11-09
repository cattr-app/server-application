<?php

namespace Tests\Feature\ProjectGroups;

use App\Models\ProjectGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'project-groups/edit';

    private ProjectGroup $parent;
    private ProjectGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = ProjectGroup::create(['name' => 'Parent group']);
        $this->group = ProjectGroup::create(['name' => 'Original']);
        $this->group->parent()->associate($this->parent)->save();
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

    public static function invalidInput(): array
    {
        return [
            'empty name' => [['name' => '']],
            'array name' => [['name' => ['Renamed']]],
            'non-integer parent' => [['parent_id' => 'abc']],
            'not existing parent' => [['parent_id' => 999999]],
        ];
    }

    #[DataProvider('allowedRoles')]
    public function test_edit(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, [
            'id' => $this->group->id,
            'name' => 'Renamed',
            'parent_id' => $this->parent->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'name', 'parent_id', 'created_at', 'updated_at']]);
        $response->assertJsonPath('data.id', $this->group->id);
        $response->assertJsonPath('data.name', 'Renamed');
        $this->assertDatabaseHas('project_groups', [
            'id' => $this->group->id,
            'name' => 'Renamed',
            'parent_id' => $this->parent->id,
        ]);
    }

    public function test_edit_moves_group_to_another_parent(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $newParent = ProjectGroup::create(['name' => 'New parent']);

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'id' => $this->group->id,
            'parent_id' => $newParent->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_groups', [
            'id' => $this->group->id,
            'name' => 'Original',
            'parent_id' => $newParent->id,
        ]);
    }

    public function test_edit_without_parent_makes_group_a_root(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->group->id, 'parent_id' => null]);

        $response->assertOk();
        $this->assertDatabaseHas('project_groups', [
            'id' => $this->group->id,
            'name' => 'Original',
            'parent_id' => null,
        ]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_edit_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->group->id, 'name' => 'Renamed']);

        $response->assertForbidden();
        $this->assertDatabaseHas('project_groups', ['id' => $this->group->id, 'name' => 'Original']);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->group->id] + $data);

        $response->assertValidationError();
        $this->assertDatabaseHas('project_groups', [
            'id' => $this->group->id,
            'name' => 'Original',
            'parent_id' => $this->parent->id,
        ]);
    }

    public function test_not_existing_group(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'id' => ProjectGroup::withTrashed()->max('id') + 1,
            'name' => 'Renamed',
        ]);

        $response->assertValidationError();
    }

    public function test_without_id(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Renamed']);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['id' => $this->group->id, 'name' => 'Renamed']);

        $response->assertUnauthorized();
        $this->assertDatabaseHas('project_groups', ['id' => $this->group->id, 'name' => 'Original']);
    }
}
