<?php

namespace Tests\Feature\Priorities;

use App\Models\Priority;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'priorities/remove';

    private Priority $priority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->priority = Priority::create(['name' => 'Disposable']);
    }

    public static function forbiddenRoles(): array
    {
        return [
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    public function test_remove_as_admin(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->priority->id]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('priorities', ['id' => $this->priority->id]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_remove_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->priority->id]);

        $response->assertForbidden();
        $this->assertDatabaseHas('priorities', ['id' => $this->priority->id]);
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
        $this->assertDatabaseHas('priorities', ['id' => $this->priority->id]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['id' => $this->priority->id]);

        $response->assertUnauthorized();
        $this->assertDatabaseHas('priorities', ['id' => $this->priority->id]);
    }
}
