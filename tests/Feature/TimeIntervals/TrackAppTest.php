<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\TrackedApplication;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TrackAppTest extends TestCase
{
    private const URI = 'time-intervals/app';

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
    public function test_track_app(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();
        $data = ['title' => 'Release notes - Editor', 'executable' => 'editor.exe'];

        $response = $this->actingAs($user)->putJson(self::URI, $data);

        $response->assertOk();
        $response->assertJsonPath('data.title', $data['title']);
        $response->assertJsonPath('data.executable', $data['executable']);
        $response->assertJsonPath('data.user_id', $user->id);
        $this->assertDatabaseHas('tracked_applications', $data + [
            'user_id' => $user->id,
            'time_interval_id' => null,
        ]);
    }

    public function test_title_is_optional(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();

        $response = $this->actingAs($user)->putJson(self::URI, ['executable' => 'editor.exe']);

        $response->assertOk();
        $this->assertDatabaseHas('tracked_applications', [
            'user_id' => $user->id,
            'executable' => 'editor.exe',
            'title' => null,
        ]);
    }

    public function test_user_id_in_the_request_is_ignored(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();
        $other = UserFactory::refresh()->asUser()->create();

        $response = $this->actingAs($user)->putJson(self::URI, [
            'executable' => 'editor.exe',
            'user_id' => $other->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tracked_applications', ['user_id' => $user->id, 'executable' => 'editor.exe']);
        $this->assertDatabaseMissing('tracked_applications', ['user_id' => $other->id]);
    }

    public function test_every_request_adds_a_record(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();
        $data = ['title' => 'Terminal', 'executable' => 'term'];

        $this->actingAs($user)->putJson(self::URI, $data)->assertOk();

        $this->assertSame(1, TrackedApplication::withoutGlobalScopes()->where('user_id', $user->id)->count());
    }

    public function test_without_executable(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();

        $response = $this->actingAs($user)->putJson(self::URI, ['title' => 'Terminal']);

        $response->assertValidationError();
        $this->assertDatabaseMissing('tracked_applications', ['user_id' => $user->id]);
    }

    public function test_title_must_be_a_string(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();

        $response = $this->actingAs($user)->putJson(self::URI, ['title' => ['a'], 'executable' => 'term']);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->putJson(self::URI, ['executable' => 'term']);

        $response->assertUnauthorized();
    }
}
