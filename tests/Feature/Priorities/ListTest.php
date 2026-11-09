<?php

namespace Tests\Feature\Priorities;

use App\Models\Priority;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'priorities/list';

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
        $response->assertJsonStructure(['data' => ['*' => ['id', 'name', 'color']]]);
        $this->assertEqualsCanonicalizing(
            Priority::pluck('id')->all(),
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_list_is_paginated_by_default(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonPath('pagination.total', Priority::count());
    }

    public function test_list_by_post(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['where' => ['name' => 'High']]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'High');
    }

    public function test_list_with_filter(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $priority = Priority::create(['name' => 'List filter priority', 'color' => '#aabbcc']);

        $response = $this->actingAs($admin)->getJson(self::URI . '?' . http_build_query([
            'where' => ['name' => $priority->name],
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $priority->id);
        $response->assertJsonPath('data.0.color', '#aabbcc');
    }

    public function test_list_with_order_and_limit(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->withHeader('X-Paginate', 'false')->actingAs($admin)->getJson(
            self::URI . '?' . http_build_query(['orderBy' => ['id', 'desc'], 'limit' => 2]),
        );

        $response->assertOk();
        $this->assertSame(
            Priority::orderByDesc('id')->limit(2)->pluck('id')->all(),
            collect($response->json('data'))->pluck('id')->all(),
        );
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
