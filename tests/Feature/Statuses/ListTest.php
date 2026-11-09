<?php

namespace Tests\Feature\Statuses;

use App\Models\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'statuses/list';

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
        $response->assertJsonStructure(['data' => ['*' => ['id', 'name', 'active', 'color', 'order']]]);
        $this->assertEqualsCanonicalizing(
            Status::pluck('id')->all(),
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_list_is_paginated_by_default(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonPath('pagination.total', Status::count());
    }

    public function test_list_by_post(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['where' => ['name' => 'Open']]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Open');
    }

    public function test_list_with_filter(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $status = Status::create([
            'name' => 'List filter status',
            'active' => false,
            'color' => '#aabbcc',
            'order' => Status::max('order') + 1,
        ]);

        $response = $this->actingAs($admin)->getJson(self::URI . '?' . http_build_query([
            'where' => ['name' => $status->name],
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $status->id);
        $response->assertJsonPath('data.0.active', false);
        $response->assertJsonPath('data.0.color', '#aabbcc');
        $response->assertJsonPath('data.0.order', $status->order);
    }

    public function test_list_with_order_and_limit(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->withHeader('X-Paginate', 'false')->actingAs($admin)->getJson(
            self::URI . '?' . http_build_query(['orderBy' => ['order', 'desc'], 'limit' => 2]),
        );

        $response->assertOk();
        $this->assertSame(
            Status::orderByDesc('order')->limit(2)->pluck('id')->all(),
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
