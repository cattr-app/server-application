<?php

namespace Tests\Feature;

use App\Enums\ScreenshotsState;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Settings;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * HTTP exceptions thrown by the framework or by abort() keep their status and headers
 * and are rendered in the standard error envelope.
 */
class HttpExceptionStatusTest extends TestCase
{
    private const THROTTLED_URI = 'auth/me';
    private const THROTTLE_LIMIT = 120;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_throttled_request_returns_429_with_rate_limit_headers(): void
    {
        for ($i = 1; $i <= self::THROTTLE_LIMIT; $i++) {
            $this->actingAs($this->user)->getJson(self::THROTTLED_URI)->assertOk();
        }

        $response = $this->actingAs($this->user)->getJson(self::THROTTLED_URI);

        $response->assertError(self::HTTP_TOO_MANY_REQUESTS, 'too_many_requests')
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.message', 'Too Many Attempts.')
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit', self::THROTTLE_LIMIT)
            ->assertHeader('X-RateLimit-Remaining', 0)
            ->assertHeader('X-RateLimit-Reset');
    }

    public function test_abort_keeps_status_and_message(): void
    {
        // Global override: screenshots are forbidden for every project.
        Settings::scope('core')->set('screenshots_state', ScreenshotsState::FORBIDDEN->value);
        $task = TaskFactory::forUser($this->user)->create();
        $interval = IntervalFactory::forUser($this->user)->forTask($task)->create();

        $response = $this->actingAs($this->user)->post(
            "time-intervals/$interval->id/screenshot",
            ['screenshot' => UploadedFile::fake()->image('screenshot.png')],
            ['Accept' => 'application/json'],
        );

        $response->assertError(self::HTTP_CONFLICT, 'conflict')
            ->assertJsonPath('error.message', 'Screenshots disabled for interval\'s project');
    }

    public function test_maintenance_mode_returns_503_with_retry_after(): void
    {
        $this->app->maintenanceMode()->activate(['retry' => 60, 'status' => self::HTTP_SERVICE_UNAVAILABLE]);

        try {
            $response = $this->actingAs($this->user)->getJson(self::THROTTLED_URI);
        } finally {
            $this->app->maintenanceMode()->deactivate();
        }

        $response->assertError(self::HTTP_SERVICE_UNAVAILABLE, 'service_unavailable')
            ->assertHeader('Retry-After', 60);
    }
}
