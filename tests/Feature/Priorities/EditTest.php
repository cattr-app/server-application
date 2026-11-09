<?php

namespace Tests\Feature\Priorities;

use App\Models\Priority;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'priorities/edit';

    private Priority $priority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->priority = Priority::create(['name' => 'Original', 'color' => '#aabbcc']);
    }

    public static function forbiddenRoles(): array
    {
        return [
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    public static function invalidInput(): array
    {
        return [
            'without name' => [['color' => '#112233']],
            'empty name' => [['name' => '']],
            'array name' => [['name' => ['Renamed']]],
            'color without hash' => [['name' => 'Renamed', 'color' => '112233']],
            'short color' => [['name' => 'Renamed', 'color' => '#123']],
            'non-hex color' => [['name' => 'Renamed', 'color' => '#gggggg']],
        ];
    }

    public function test_edit_as_admin(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'id' => $this->priority->id,
            'name' => 'Renamed',
            'color' => '#112233',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'name', 'color', 'created_at', 'updated_at']]);
        $response->assertJsonPath('data.id', $this->priority->id);
        $response->assertJsonPath('data.name', 'Renamed');
        $this->assertDatabaseHas('priorities', [
            'id' => $this->priority->id,
            'name' => 'Renamed',
            'color' => '#112233',
        ]);
    }

    public function test_edit_resets_color(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'id' => $this->priority->id,
            'name' => 'Original',
            'color' => null,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('priorities', ['id' => $this->priority->id, 'name' => 'Original', 'color' => null]);
    }

    public function test_edit_without_color_keeps_it(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->priority->id, 'name' => 'Renamed']);

        $response->assertOk();
        $this->assertDatabaseHas('priorities', [
            'id' => $this->priority->id,
            'name' => 'Renamed',
            'color' => '#aabbcc',
        ]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_edit_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->priority->id, 'name' => 'Renamed']);

        $response->assertForbidden();
        $this->assertDatabaseHas('priorities', ['id' => $this->priority->id, 'name' => 'Original']);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->priority->id] + $data);

        $response->assertValidationError();
        $this->assertDatabaseHas('priorities', [
            'id' => $this->priority->id,
            'name' => 'Original',
            'color' => '#aabbcc',
        ]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['id' => $this->priority->id, 'name' => 'Renamed']);

        $response->assertUnauthorized();
        $this->assertDatabaseHas('priorities', ['id' => $this->priority->id, 'name' => 'Original']);
    }
}
