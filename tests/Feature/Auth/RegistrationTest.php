<?php

namespace Tests\Feature\Auth;

use App\Enums\ScreenshotsState;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Factories\UserFactory as Roles;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * The registration routes sit behind auth:sanctum, so every request here is made by an already signed-in user.
 */
class RegistrationTest extends TestCase
{
    private const URI = 'auth/register/';

    private User $caller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->caller = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    public static function roles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    private function invitation(array $attributes = []): Invitation
    {
        return Invitation::create($attributes + [
            'email' => fake()->unique()->safeEmail(),
            'key' => fake()->uuid(),
            'expires_at' => now()->addDay(),
            'role_id' => Roles::MANAGER_ROLE,
        ]);
    }

    private function registrationData(Invitation $invitation, array $overrides = []): array
    {
        return $overrides + [
            'email' => $invitation->email,
            'full_name' => 'Invited Person',
            'password' => 'a-strong-password',
        ];
    }

    public function test_guest_cannot_open_or_submit_the_form(): void
    {
        $invitation = $this->invitation();

        $this->getJson(self::URI . $invitation->key)->assertUnauthorized();
        $this->postJson(self::URI . $invitation->key, $this->registrationData($invitation))->assertUnauthorized();

        $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
        $this->assertDatabaseMissing('users', ['email' => $invitation->email]);
    }

    #[DataProvider('roles')]
    public function test_form_returns_the_invited_email(string $role): void
    {
        $invitation = $this->invitation();
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $this->actingAs($user)->getJson(self::URI . $invitation->key)
            ->assertOk()
            ->assertJsonPath('data.email', $invitation->email);
    }

    public function test_form_of_an_unknown_key_is_not_found(): void
    {
        $this->actingAs($this->caller)->getJson(self::URI . fake()->uuid())
            ->assertStatus(404)
            ->assertJsonPath('message', 'The specified key has expired or does not exist');
    }

    public function test_registration_creates_the_user_with_the_invited_role_and_password(): void
    {
        $invitation = $this->invitation(['role_id' => Roles::AUDITOR_ROLE]);

        $response = $this->actingAs($this->caller)
            ->postJson(self::URI . $invitation->key, $this->registrationData($invitation));

        $response->assertOk()->assertJsonStructure(['data' => ['user_id']]);

        $user = User::withoutGlobalScopes()->findOrFail($response->json('data.user_id'));
        $this->assertSame($invitation->email, $user->email);
        $this->assertSame('Invited Person', $user->full_name);
        $this->assertSame(Roles::AUDITOR_ROLE, $user->role_id);
        $this->assertEquals(1, $user->active);
        $this->assertEquals(0, $user->manual_time);
        $this->assertSame(ScreenshotsState::REQUIRED, $user->screenshots_state);
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
    }

    public function test_the_invitation_is_consumed(): void
    {
        $invitation = $this->invitation();

        $this->actingAs($this->caller)
            ->postJson(self::URI . $invitation->key, $this->registrationData($invitation))
            ->assertOk();

        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
    }

    public function test_the_key_cannot_be_used_twice(): void
    {
        $invitation = $this->invitation();
        $data = $this->registrationData($invitation);

        $this->actingAs($this->caller)->postJson(self::URI . $invitation->key, $data)->assertOk();
        $this->postJson(self::URI . $invitation->key, $data)->assertStatus(404);
        $this->getJson(self::URI . $invitation->key)->assertStatus(404);

        $this->assertSame(1, User::withoutGlobalScopes()->where('email', $invitation->email)->count());
    }

    public function test_form_of_an_expired_key_is_not_found(): void
    {
        $invitation = $this->invitation(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->caller)->getJson(self::URI . $invitation->key)->assertStatus(404);
    }

    public function test_registration_with_an_expired_key_is_not_found(): void
    {
        $invitation = $this->invitation(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->caller)
            ->postJson(self::URI . $invitation->key, $this->registrationData($invitation))
            ->assertStatus(404);

        $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
        $this->assertDatabaseMissing('users', ['email' => $invitation->email]);
    }

    public function test_a_key_expiring_in_the_future_still_works(): void
    {
        $invitation = $this->invitation(['expires_at' => now()->addMinute()]);

        $this->actingAs($this->caller)->getJson(self::URI . $invitation->key)->assertOk();
        $this->postJson(self::URI . $invitation->key, $this->registrationData($invitation))->assertOk();
    }

    public function test_registration_with_an_unknown_key_is_not_found(): void
    {
        $this->actingAs($this->caller)
            ->postJson(self::URI . fake()->uuid(), ['email' => 'nobody@example.com', 'password' => 'secret-pass'])
            ->assertStatus(404);

        $this->assertDatabaseMissing('users', ['email' => 'nobody@example.com']);
    }

    public function test_registration_with_another_email_is_rejected(): void
    {
        $invitation = $this->invitation();

        $this->actingAs($this->caller)
            ->postJson(
                self::URI . $invitation->key,
                $this->registrationData($invitation, ['email' => 'other@example.com'])
            )
            ->assertBadRequest()
            ->assertJsonPath('message', 'The email address does not match the key');

        $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
        $this->assertDatabaseMissing('users', ['email' => 'other@example.com']);
    }
}
