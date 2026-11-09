<?php

namespace Tests\Feature\Statuses;

use App\Models\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'statuses/edit';

    private Status $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->status = Status::create([
            'name' => 'Original',
            'active' => true,
            'color' => '#aabbcc',
            'order' => Status::max('order') + 1,
        ]);
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
            'without name' => [['active' => false]],
            'empty name' => [['name' => '']],
            'array name' => [['name' => ['Renamed']]],
            'non-integer order' => [['name' => 'Renamed', 'order' => 'first']],
            'not existing order' => [['name' => 'Renamed', 'order' => 9999]],
            'non-boolean active' => [['name' => 'Renamed', 'active' => 'maybe']],
            'color without hash' => [['name' => 'Renamed', 'color' => '112233']],
            'short color' => [['name' => 'Renamed', 'color' => '#123']],
            'non-hex color' => [['name' => 'Renamed', 'color' => '#gggggg']],
        ];
    }

    public function test_edit_as_admin(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'id' => $this->status->id,
            'name' => 'Renamed',
            'active' => false,
            'color' => '#112233',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'active', 'color', 'order', 'created_at', 'updated_at'],
        ]);
        $response->assertJsonPath('data.id', $this->status->id);
        $response->assertJsonPath('data.name', 'Renamed');
        $response->assertJsonPath('data.active', false);
        $this->assertDatabaseHas('statuses', [
            'id' => $this->status->id,
            'name' => 'Renamed',
            'active' => 0,
            'color' => '#112233',
            'order' => $this->status->order,
        ]);
    }

    public function test_edit_resets_color(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'id' => $this->status->id,
            'name' => 'Original',
            'color' => null,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('statuses', ['id' => $this->status->id, 'color' => null]);
    }

    public function test_edit_order_swaps_statuses(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $first = Status::where('order', 1)->firstOrFail();
        $oldOrder = $this->status->order;

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'id' => $this->status->id,
            'name' => 'Original',
            'order' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.order', 1);
        $this->assertDatabaseHas('statuses', ['id' => $this->status->id, 'order' => 1]);
        $this->assertDatabaseHas('statuses', ['id' => $first->id, 'order' => $oldOrder]);
    }

    #[DataProvider('forbiddenRoles')]
    public function test_edit_is_forbidden(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->status->id, 'name' => 'Renamed']);

        $response->assertForbidden();
        $this->assertDatabaseHas('statuses', ['id' => $this->status->id, 'name' => 'Original']);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->status->id] + $data);

        $response->assertValidationError();
        $this->assertDatabaseHas('statuses', [
            'id' => $this->status->id,
            'name' => 'Original',
            'active' => 1,
            'color' => '#aabbcc',
            'order' => $this->status->order,
        ]);
    }

    public function test_not_existing_status(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => Status::max('id') + 1, 'name' => 'Renamed']);

        $response->assertValidationError();
    }

    public function test_without_id(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['name' => 'Renamed']);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['id' => $this->status->id, 'name' => 'Renamed']);

        $response->assertUnauthorized();
        $this->assertDatabaseHas('statuses', ['id' => $this->status->id, 'name' => 'Original']);
    }
}
