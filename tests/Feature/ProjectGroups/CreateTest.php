<?php

namespace Tests\Feature\ProjectGroups;

use App\Models\ProjectGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'project-groups/create';

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
            'without name' => [[]],
            'empty name' => [['name' => '']],
            'array name' => [['name' => ['Group']]],
            'non-integer parent' => [['name' => 'Group', 'parent_id' => 'abc']],
            'not existing parent' => [['name' => 'Group', 'parent_id' => 999999]],
        ];
    }

    #[DataProvider('allowedRoles')]
    public function test_create(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['name' => 'New group']);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'name', 'created_at', 'updated_at']]);
        $response->assertJsonPath('data.name', 'New group');
        $this->assertDatabaseHas('project_groups', [
            'id' => $response->json('data.id'),
            'name' => 'New group',
            'parent_id' => null,
        ]);
    }

    public function test_create_nested_group(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $parent = ProjectGroup::create(['name' => 'Parent group']);

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Child group', 'parent_id' => $parent->id]);

        $response->assertOk();
        $this->assertDatabaseHas('project_groups', [
            'id' => $response->json('data.id'),
            'name' => 'Child group',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_create_with_removed_parent_is_rejected(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $parent = ProjectGroup::create(['name' => 'Removed parent']);
        $parent->delete();

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Child', 'parent_id' => $parent->id]);

        $response->assertValidationError();
    }

    public function test_create_with_null_parent(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Root group', 'parent_id' => null]);

        $response->assertOk();
        $this->assertDatabaseHas('project_groups', [
            'id' => $response->json('data.id'),
            'name' => 'Root group',
            'parent_id' => null,
        ]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_create_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['name' => 'New group']);

        $response->assertForbidden();
        $this->assertDatabaseMissing('project_groups', ['name' => 'New group']);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $count = ProjectGroup::count();

        $response = $this->actingAs($admin)->postJson(self::URI, $data);

        $response->assertValidationError();
        $this->assertSame($count, ProjectGroup::count());
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['name' => 'New group']);

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('project_groups', ['name' => 'New group']);
    }
}
