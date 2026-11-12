<?php

namespace Tests\Feature\Statuses;

use App\Models\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'statuses/count';

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
    public function test_count(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertExactJson(['total' => Status::count()]);
    }

    public function test_count_with_filter(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        Status::create(['name' => 'Count filter status', 'active' => true, 'order' => Status::max('order') + 1]);

        $response = $this->actingAs($admin)->getJson(self::URI . '?' . http_build_query([
            'where' => ['name' => 'Count filter status'],
        ]));

        $response->assertOk();
        $response->assertExactJson(['total' => 1]);
    }

    public function test_count_with_invalid_filter(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?where=name');

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
