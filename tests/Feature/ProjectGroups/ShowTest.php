<?php

namespace Tests\Feature\ProjectGroups;

use App\Models\ProjectGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'project-groups/show';

    private ProjectGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = ProjectGroup::create(['name' => 'Shown group']);
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
    public function test_show(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI . "?id={$this->group->id}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'name', 'parent_id', 'created_at', 'updated_at']]);
        $response->assertJsonPath('data.id', $this->group->id);
        $response->assertJsonPath('data.name', 'Shown group');
        $response->assertJsonPath('data.parent_id', null);
    }

    public function test_show_by_post(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->group->id]);

        $response->assertOk();
        $response->assertJsonPath('data.id', $this->group->id);
    }

    public function test_show_with_permissions(): void
    {
        $manager = UserFactory::asManager()->withTokens()->create();

        $response = $this->actingAs($manager)->getJson(self::URI . "?id={$this->group->id}&with[]=can");

        $response->assertOk();
        $response->assertJsonPath('data.can', ['update' => true, 'destroy' => true]);
    }

    public function test_show_with_permissions_as_user(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI . "?id={$this->group->id}&with[]=can");

        $response->assertOk();
        $response->assertJsonPath('data.can', ['update' => false, 'destroy' => false]);
    }

    public function test_removed_group_is_rejected(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $this->group->delete();

        $response = $this->actingAs($admin)->getJson(self::URI . "?id={$this->group->id}");

        $response->assertValidationError();
    }

    public function test_not_existing_group(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . (ProjectGroup::withTrashed()->max('id') + 1));

        $response->assertValidationError();
    }

    public function test_non_integer_id(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=abc');

        $response->assertValidationError();
    }

    public function test_without_params(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI . "?id={$this->group->id}");

        $response->assertUnauthorized();
    }
}
