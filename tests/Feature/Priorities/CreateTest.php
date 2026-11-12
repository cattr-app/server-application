<?php

namespace Tests\Feature\Priorities;

use App\Models\Priority;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'priorities/create';

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
            'without name' => [['color' => '#aabbcc']],
            'empty name' => [['name' => '']],
            'array name' => [['name' => ['Urgent']]],
            'color without hash' => [['name' => 'Urgent', 'color' => 'aabbcc']],
            'short color' => [['name' => 'Urgent', 'color' => '#abc']],
            'non-hex color' => [['name' => 'Urgent', 'color' => '#gggggg']],
        ];
    }

    public function test_create_as_admin(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Urgent', 'color' => '#AABBCC']);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'name', 'color', 'created_at', 'updated_at']]);
        $response->assertJsonPath('data.name', 'Urgent');
        $response->assertJsonPath('data.color', '#AABBCC');
        $this->assertDatabaseHas('priorities', [
            'id' => $response->json('data.id'),
            'name' => 'Urgent',
            'color' => '#AABBCC',
        ]);
    }

    public function test_create_without_color(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Urgent', 'color' => null]);

        $response->assertOk();
        $response->assertJsonPath('data.color', null);
        $this->assertDatabaseHas('priorities', [
            'id' => $response->json('data.id'),
            'name' => 'Urgent',
            'color' => null,
        ]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_create_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['name' => 'Urgent']);

        $response->assertForbidden();
        $this->assertDatabaseMissing('priorities', ['name' => 'Urgent']);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $count = Priority::count();

        $response = $this->actingAs($admin)->postJson(self::URI, $data);

        $response->assertValidationError();
        $this->assertSame($count, Priority::count());
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['name' => 'Urgent']);

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('priorities', ['name' => 'Urgent']);
    }
}
