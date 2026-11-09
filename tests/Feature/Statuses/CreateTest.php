<?php

namespace Tests\Feature\Statuses;

use App\Models\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'statuses/create';

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
            'without name' => [['active' => true]],
            'empty name' => [['name' => '']],
            'array name' => [['name' => ['Review']]],
            'non-integer order' => [['name' => 'Review', 'order' => 'first']],
            'non-boolean active' => [['name' => 'Review', 'active' => 'maybe']],
            'color without hash' => [['name' => 'Review', 'color' => 'aabbcc']],
            'short color' => [['name' => 'Review', 'color' => '#abc']],
            'non-hex color' => [['name' => 'Review', 'color' => '#gggggg']],
        ];
    }

    public function test_create_as_admin(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'name' => 'Review',
            'active' => false,
            'color' => '#AABBCC',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'active', 'color', 'order', 'created_at', 'updated_at'],
        ]);
        $response->assertJsonPath('data.name', 'Review');
        $response->assertJsonPath('data.active', false);
        $response->assertJsonPath('data.color', '#AABBCC');
        $this->assertDatabaseHas('statuses', [
            'id' => $response->json('data.id'),
            'name' => 'Review',
            'active' => 0,
            'color' => '#AABBCC',
        ]);
    }

    public function test_create_is_appended_to_the_end_regardless_of_order(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $maxOrder = Status::max('order');

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Review', 'active' => true, 'order' => 1]);

        $response->assertOk();
        $response->assertJsonPath('data.order', $maxOrder + 1);
        $this->assertDatabaseHas('statuses', ['id' => $response->json('data.id'), 'order' => $maxOrder + 1]);
    }

    public function test_create_without_active(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Review']);

        $response->assertOk();
        $this->assertDatabaseHas('statuses', ['id' => $response->json('data.id'), 'name' => 'Review']);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_create_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['name' => 'Review', 'active' => true]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('statuses', ['name' => 'Review']);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $count = Status::count();

        $response = $this->actingAs($admin)->postJson(self::URI, $data);

        $response->assertValidationError();
        $this->assertSame($count, Status::count());
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['name' => 'Review', 'active' => true]);

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('statuses', ['name' => 'Review']);
    }
}
