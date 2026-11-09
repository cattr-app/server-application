<?php

namespace Tests\Feature\Priorities;

use App\Models\Priority;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'priorities/show';

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
        $priority = Priority::create(['name' => 'Shown priority', 'color' => '#aabbcc']);

        $response = $this->actingAs($user)->getJson(self::URI . "?id={$priority->id}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'name', 'color', 'created_at', 'updated_at']]);
        $response->assertJsonPath('data.id', $priority->id);
        $response->assertJsonPath('data.name', 'Shown priority');
        $response->assertJsonPath('data.color', '#aabbcc');
    }

    public function test_show_by_post(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $priority = Priority::firstOrFail();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $priority->id]);

        $response->assertOk();
        $response->assertJsonPath('data.id', $priority->id);
    }

    public static function invalidIds(): array
    {
        return [
            'missing id' => [[]],
            'non-integer id' => [['id' => 'abc']],
            'non-existent id' => [['id' => 999999]],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_invalid_id(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, $data);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI . '?id=' . Priority::firstOrFail()->id);

        $response->assertUnauthorized();
    }
}
