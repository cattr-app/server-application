<?php

namespace Tests\Feature\Statuses;

use App\Models\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'statuses/remove';

    private Status $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->status = Status::create(['name' => 'Disposable', 'active' => true, 'order' => Status::max('order') + 1]);
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

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->status->id]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('statuses', ['id' => $this->status->id]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_remove_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->status->id]);

        $response->assertForbidden();
        $this->assertDatabaseHas('statuses', ['id' => $this->status->id]);
    }

    public function test_not_existing_status(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => Status::max('id') + 1]);

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
        $response = $this->postJson(self::URI, ['id' => $this->status->id]);

        $response->assertUnauthorized();
        $this->assertDatabaseHas('statuses', ['id' => $this->status->id]);
    }
}
