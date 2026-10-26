<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Enums\ScreenshotsState;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Screenshots on the local disk are served by signed routes, on S3 by presigned URLs.
 */
class ScreenshotTest extends TestCase
{
    private User $admin;
    private User $user;
    private TimeInterval $interval;
    private ScreenshotService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->interval = IntervalFactory::create();
        $this->service = app(ScreenshotService::class);
    }

    private function fakeImage(): string
    {
        return UploadedFile::fake()->image('screenshot.jpg', 640, 360)->getContent();
    }

    private function useS3(): void
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');
    }

    public function test_list_contains_signed_urls_and_no_has_screenshot(): void
    {
        $response = $this->actingAs($this->admin)->postJson('time-intervals/list', ['id' => $this->interval->id]);

        $response->assertOk();
        $item = $response->json('data.0') ?? $response->json('0');
        $this->assertSame($this->interval->id, $item['id']);
        $this->assertArrayNotHasKey('has_screenshot', $item);
        $this->assertStringStartsWith("/api/time-intervals/{$this->interval->id}/screenshot?", $item['screenshot_url']);
        $this->assertStringStartsWith("/api/time-intervals/{$this->interval->id}/thumb?", $item['thumbnail_url']);
        $this->assertStringContainsString('signature=', $item['thumbnail_url']);
    }

    public function test_show_contains_signed_urls_and_no_has_screenshot(): void
    {
        $response = $this->actingAs($this->admin)->postJson('time-intervals/show', ['id' => $this->interval->id]);

        $response->assertOk();
        $response->assertJsonMissingPath('data.has_screenshot');
        $this->assertStringContainsString('signature=', $response->json('data.screenshot_url'));
        $this->assertStringContainsString('signature=', $response->json('data.thumbnail_url'));
    }

    public function test_model_does_not_append_urls_by_default(): void
    {
        $attributes = $this->interval->fresh()->toArray();

        $this->assertArrayNotHasKey('has_screenshot', $attributes);
        $this->assertArrayNotHasKey('screenshot_url', $attributes);
        $this->assertArrayNotHasKey('thumbnail_url', $attributes);
    }

    public function test_signed_url_ttl_is_ten_minutes(): void
    {
        $this->freezeSecond();

        parse_str(parse_url($this->service->getTemporaryUrl($this->interval), PHP_URL_QUERY), $query);

        $this->assertSame(10, ScreenshotService::URL_TTL_MINUTES);
        $this->assertSame(now()->addMinutes(10)->getTimestamp(), (int)$query['expires']);
    }

    public function test_signed_route_serves_thumbnail_without_auth(): void
    {
        $image = $this->fakeImage();
        Storage::put($this->service->getThumbPath($this->interval), $image);

        $response = $this->get($this->service->getTemporaryUrl($this->interval, true));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame($image, $response->streamedContent());
    }

    public function test_signed_route_serves_screenshot_without_auth(): void
    {
        $image = $this->fakeImage();
        Storage::put($this->service->getScreenshotPath($this->interval), $image);

        $response = $this->get($this->service->getTemporaryUrl($this->interval));

        $response->assertOk();
        $this->assertSame($image, $response->streamedContent());
    }

    public function test_signed_route_missing_file_is_not_found(): void
    {
        $this->get($this->service->getTemporaryUrl($this->interval))->assertNotFound('page_not_found');
    }

    public function test_unsigned_url_is_forbidden(): void
    {
        Storage::put($this->service->getThumbPath($this->interval), $this->fakeImage());

        $this->get("time-intervals/{$this->interval->id}/thumb")->assertForbidden();
        $this->actingAs($this->admin)->get("time-intervals/{$this->interval->id}/screenshot")->assertForbidden();
    }

    public function test_signature_of_another_interval_is_forbidden(): void
    {
        $other = IntervalFactory::create();
        Storage::put($this->service->getThumbPath($other), $this->fakeImage());

        $url = str_replace(
            "/time-intervals/{$this->interval->id}/",
            "/time-intervals/$other->id/",
            $this->service->getTemporaryUrl($this->interval, true),
        );

        $this->get($url)->assertForbidden();
    }

    public function test_signed_routes_are_throttled_per_ip(): void
    {
        $this->assertSame(600, config('app.screenshots_rate_limit'));
        config(['app.screenshots_rate_limit' => 3]);
        Storage::put($this->service->getThumbPath($this->interval), $this->fakeImage());
        Storage::put($this->service->getScreenshotPath($this->interval), $this->fakeImage());

        // thumbnails and screenshots share one budget
        $this->get($this->service->getTemporaryUrl($this->interval, true))->assertOk()
            ->assertHeader('X-RateLimit-Limit', 3);
        $this->get($this->service->getTemporaryUrl($this->interval))->assertOk();
        $this->get($this->service->getTemporaryUrl($this->interval, true))->assertOk()
            ->assertHeader('X-RateLimit-Remaining', 0);

        // Asserted as the exception: App\Exceptions\Handler currently renders HTTP exceptions that are not
        // responder ones (ThrottleRequestsException, InvalidSignatureException) as 500, fixed separately.
        $this->withoutExceptionHandling();
        try {
            $this->get($this->service->getTemporaryUrl($this->interval));
            $this->fail('The 4th request within a minute must be throttled');
        } catch (ThrottleRequestsException $e) {
            $this->assertSame(429, $e->getStatusCode());
        }

        // another client is not affected
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->get($this->service->getTemporaryUrl($this->interval, true))->assertOk();

        $this->travel(61)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get($this->service->getTemporaryUrl($this->interval, true))->assertOk();
    }

    public function test_expired_url_is_forbidden(): void
    {
        Storage::put($this->service->getThumbPath($this->interval), $this->fakeImage());
        $url = $this->service->getTemporaryUrl($this->interval, true);

        $this->travel(9)->minutes();
        $this->get($url)->assertOk();

        $this->travel(2)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_screenshot_urls_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->getJson("time-intervals/{$this->interval->id}/screenshot-urls");

        $response->assertOk();
        $response->assertJsonPath('data.expires_in', 600);
        $this->assertStringContainsString('signature=', $response->json('data.screenshot_url'));
        $this->assertStringContainsString('signature=', $response->json('data.thumbnail_url'));
    }

    public function test_screenshot_urls_endpoint_requires_access(): void
    {
        $this->getJson("time-intervals/{$this->interval->id}/screenshot-urls")->assertUnauthorized();
        // intervals outside the user's access scope are not even found, as with time-intervals/show
        $this->actingAs($this->user)->getJson("time-intervals/{$this->interval->id}/screenshot-urls")
            ->assertNotFound('page_not_found');
    }

    private function createIntervalWithScreenshot(): TimeInterval
    {
        /** @var Task $task */
        $task = TaskFactory::forUser($this->admin)->create();
        $task->project->screenshots_state = ScreenshotsState::REQUIRED;
        $task->project->save();

        $data = IntervalFactory::createRandomModelData();
        $data['task_id'] = $task->id;
        $data['user_id'] = $this->admin->id;
        $data['screenshot'] = UploadedFile::fake()->image('screenshot.jpg', 1280, 720);

        $response = $this->actingAs($this->admin)
            ->post('time-intervals/create', $data, ['Accept' => 'application/json']);
        $response->assertOk();

        return TimeInterval::findOrFail($response->json('data.id'));
    }

    public function test_upload_stores_screenshot_and_thumbnail_on_local_disk(): void
    {
        $interval = $this->createIntervalWithScreenshot();

        Storage::disk('local')->assertExists($this->service->getScreenshotPath($interval));
        Storage::disk('local')->assertExists($this->service->getThumbPath($interval));
        $this->assertSame([], Storage::disk('local')->files('tmp'));
    }

    public function test_upload_stores_screenshot_and_thumbnail_on_s3(): void
    {
        $this->useS3();

        $interval = $this->createIntervalWithScreenshot();

        Storage::disk('s3')->assertExists($this->service->getScreenshotPath($interval));
        Storage::disk('s3')->assertExists($this->service->getThumbPath($interval));
        // the upload itself is only a temporary local copy
        Storage::disk('s3')->assertMissing('tmp');
        Storage::disk('local')->assertMissing($this->service->getScreenshotPath($interval));

        [$width] = getimagesizefromstring(Storage::disk('s3')->get($this->service->getThumbPath($interval)));
        $this->assertSame(280, $width);
    }

    public function test_thumbnail_is_generated_from_s3(): void
    {
        $this->useS3();
        Storage::disk('s3')->put($this->service->getScreenshotPath($this->interval), $this->fakeImage());

        $this->service->createThumbnail($this->interval);

        Storage::disk('s3')->assertExists($this->service->getThumbPath($this->interval));
    }

    public function test_s3_payload_contains_presigned_urls(): void
    {
        $this->useS3();
        $this->freezeSecond();

        $response = $this->actingAs($this->admin)->postJson('time-intervals/show', ['id' => $this->interval->id]);

        $response->assertOk();
        $expiration = now()->addMinutes(10)->getTimestamp();
        // Storage::fake() builds presigned-like URLs as <path>?expiration=<timestamp>
        $this->assertStringEndsWith(
            $this->service->getScreenshotPath($this->interval) . "?expiration=$expiration",
            $response->json('data.screenshot_url'),
        );
        $this->assertStringEndsWith(
            $this->service->getThumbPath($this->interval) . "?expiration=$expiration",
            $response->json('data.thumbnail_url'),
        );
    }
}
