<?php

namespace Tests\Feature\Statuses;

use App\Models\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'statuses/show';

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
        $status = Status::create([
            'name' => 'Shown status',
            'active' => true,
            'color' => '#aabbcc',
            'order' => Status::max('order') + 1,
        ]);

        $response = $this->actingAs($user)->getJson(self::URI . "?id={$status->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'active', 'color', 'order', 'created_at', 'updated_at'],
        ]);
        $response->assertJsonPath('data.id', $status->id);
        $response->assertJsonPath('data.name', 'Shown status');
        $response->assertJsonPath('data.active', true);
        $response->assertJsonPath('data.color', '#aabbcc');
        $response->assertJsonPath('data.order', $status->order);
    }

    public function test_show_by_post(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $status = Status::firstOrFail();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $status->id]);

        $response->assertOk();
        $response->assertJsonPath('data.id', $status->id);
    }

    public function test_not_existing_status(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . (Status::max('id') + 1));

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
        $response = $this->getJson(self::URI . '?id=' . Status::firstOrFail()->id);

        $response->assertUnauthorized();
    }
}
