<?php

namespace Tests\Feature\Users;

use App\Enums\ScreenshotsState;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * screenshots_state_locked mirrors users/create: an admin locks the state for the user when the
 * user's own state is forbidden or required. Global overrides (env / company settings) are applied
 * at read time by the User::screenshotsState() accessor and must not be persisted into the lock.
 */
class EditScreenshotsStateTest extends TestCase
{
    private const URI = '/users/edit';

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    public static function states(): array
    {
        return [
            'forbidden' => [ScreenshotsState::FORBIDDEN, true],
            'required' => [ScreenshotsState::REQUIRED, true],
            'optional' => [ScreenshotsState::OPTIONAL, false],
            'any' => [ScreenshotsState::ANY, false],
        ];
    }

    private function storeState(User $user, ScreenshotsState $state, bool $locked): void
    {
        $user->forceFill(['screenshots_state' => $state, 'screenshots_state_locked' => $locked])->saveQuietly();
    }

    #[DataProvider('states')]
    public function test_admin_sets_screenshots_state(ScreenshotsState $state, bool $locked): void
    {
        $this->storeState($this->user, ScreenshotsState::OPTIONAL, !$locked);

        $response = $this->actingAs($this->admin)->postJson(self::URI, [
            'id' => $this->user->id,
            'screenshots_state' => $state->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.screenshots_state_locked', $locked);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'screenshots_state' => (string)$state->value,
            'screenshots_state_locked' => $locked,
        ]);
    }

    #[DataProvider('states')]
    public function test_admin_edit_without_screenshots_state_keeps_state(ScreenshotsState $state, bool $locked): void
    {
        $this->storeState($this->user, $state, !$locked);

        $response = $this->actingAs($this->admin)->postJson(self::URI, [
            'id' => $this->user->id,
            'full_name' => 'Renamed User',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.full_name', 'Renamed User');
        $response->assertJsonPath('data.screenshots_state_locked', $locked);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'full_name' => 'Renamed User',
            'screenshots_state' => (string)$state->value,
            'screenshots_state_locked' => $locked,
        ]);
    }

    public function test_admin_edit_of_own_profile_uses_stored_state(): void
    {
        // For the user himself the accessor reports optional as required; the lock must not follow that.
        $this->storeState($this->admin, ScreenshotsState::OPTIONAL, false);

        $response = $this->actingAs($this->admin)->postJson(self::URI, [
            'id' => $this->admin->id,
            'full_name' => 'Renamed Admin',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.screenshots_state_locked', false);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'screenshots_state_locked' => false]);
    }

    public function test_global_override_is_not_persisted_into_lock(): void
    {
        Settings::scope('core')->set('screenshots_state', ScreenshotsState::REQUIRED->value);
        $this->storeState($this->user, ScreenshotsState::OPTIONAL, false);

        $response = $this->actingAs($this->admin)->postJson(self::URI, [
            'id' => $this->user->id,
            'full_name' => 'Renamed User',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.screenshots_state_locked', false);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'screenshots_state' => (string)ScreenshotsState::OPTIONAL->value,
            'screenshots_state_locked' => false,
        ]);
    }

    public function test_user_cannot_change_locked_state(): void
    {
        $this->storeState($this->user, ScreenshotsState::REQUIRED, true);

        $response = $this->actingAs($this->user)->postJson(self::URI, [
            'id' => $this->user->id,
            'screenshots_state' => ScreenshotsState::OPTIONAL->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'screenshots_state' => (string)ScreenshotsState::REQUIRED->value,
            'screenshots_state_locked' => true,
        ]);
    }
}
