<?php

namespace Tests\Feature\ProjectGroups;

use App\Models\ProjectGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'project-groups/list';

    private ProjectGroup $parent;
    private ProjectGroup $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = ProjectGroup::create(['name' => 'Parent group']);
        $this->child = ProjectGroup::create(['name' => 'Child group']);
        $this->child->parent()->associate($this->parent)->save();
    }

    public static function roles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('roles')]
    public function test_list(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->withHeader('X-Paginate', 'false')->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => ['id', 'name', 'parent_id', 'depth', 'projects_count']]]);
        $this->assertEqualsCanonicalizing(
            ProjectGroup::pluck('id')->all(),
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_list_exposes_depth_and_projects_count(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $project = ProjectFactory::create();
        $project->group = $this->child->id;
        $project->save();

        $response = $this->withHeader('X-Paginate', 'false')->actingAs($admin)->getJson(
            self::URI . '?' . http_build_query(['where' => ['name' => 'Child group']]),
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->child->id);
        $response->assertJsonPath('data.0.parent_id', $this->parent->id);
        $response->assertJsonPath('data.0.depth', 1);
        $response->assertJsonPath('data.0.projects_count', 1);
    }

    public function test_list_is_paginated_by_default(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonPath('pagination.total', ProjectGroup::count());
    }

    public function test_list_by_post(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['where' => ['name' => 'Parent group']]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->parent->id);
        $response->assertJsonPath('data.0.depth', 0);
    }

    public function test_list_with_limit(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->withHeader('X-Paginate', 'false')->actingAs($admin)->getJson(self::URI . '?limit=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_list_ignores_removed_groups(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $removed = ProjectGroup::create(['name' => 'Removed group']);
        $removed->delete();

        $response = $this->withHeader('X-Paginate', 'false')->actingAs($admin)->getJson(self::URI);

        $response->assertOk();
        $this->assertNotContains($removed->id, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_list_with_invalid_limit(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?limit=many');

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
