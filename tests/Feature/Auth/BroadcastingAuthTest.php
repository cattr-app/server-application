<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class BroadcastingAuthTest extends TestCase
{
    // An absolute URL: TestCase prefixes relative paths with /api, this route lives outside of it
    private const URI = 'http://localhost/broadcasting/auth';
    private const SOCKET_ID = '1234.5678';

    protected function setUp(): void
    {
        parent::setUp();

        // The test environment broadcasts to the log, which authorizes every channel: use the real driver
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        // The channels were registered on the log driver at boot, register them on the one in use now
        require base_path('routes/channels.php');
    }

    public static function channels(): array
    {
        return [
            'tasks' => ['tasks'],
            'projects' => ['projects'],
            'gantt' => ['gantt'],
            'intervals' => ['intervals'],
            'tasks activities' => ['tasks_activities'],
        ];
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

    private function authorizeChannel(string $channel, mixed $user = null): \Tests\TestResponse
    {
        $request = $user ? $this->actingAs($user) : $this;

        return $request->postJson(self::URI, ['socket_id' => self::SOCKET_ID, 'channel_name' => "private-$channel"]);
    }

    public function test_guest_is_unauthorized(): void
    {
        $user = UserFactory::refresh()->asUser()->create();

        $this->authorizeChannel("tasks.{$user->id}")->assertUnauthorized();
    }

    #[DataProvider('roles')]
    public function test_every_role_can_listen_to_its_own_channel(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $this->authorizeChannel("tasks.{$user->id}", $user)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    #[DataProvider('channels')]
    public function test_each_channel_is_open_to_its_owner(string $channel): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->authorizeChannel("$channel.{$user->id}", $user)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    #[DataProvider('channels')]
    public function test_each_channel_is_closed_to_other_users(string $channel): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();
        $other = UserFactory::refresh()->asUser()->create();

        $this->authorizeChannel("$channel.{$other->id}", $user)->assertForbidden();
    }

    public function test_an_admin_cannot_listen_to_the_channel_of_another_user(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $other = UserFactory::refresh()->asUser()->create();

        $this->authorizeChannel("tasks.{$other->id}", $admin)->assertForbidden();
    }

    public function test_an_unknown_channel_is_closed(): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->authorizeChannel("unknown.{$user->id}", $user)->assertForbidden();
    }

    public function test_the_endpoint_answers_to_get_as_well(): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->actingAs($user)
            ->getJson(self::URI . '?' . http_build_query([
                'socket_id' => self::SOCKET_ID,
                'channel_name' => "private-gantt.{$user->id}",
            ]))
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }
}
