<?php

namespace Tests\Feature;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\IntervalFactory;
use Tests\TestCase;

/**
 * Only the bundled Caddy (127.0.0.1) may set the client address through X-Forwarded-For.
 */
class TrustedProxiesTest extends TestCase
{
    private const string CADDY = '127.0.0.1';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('api/_test/client-ip', static fn (Request $request) => $request->ip());
    }

    private function clientIp(string $remoteAddr, ?string $forwardedFor = null): string
    {
        $request = $this->withServerVariables(['REMOTE_ADDR' => $remoteAddr]);
        if ($forwardedFor !== null) {
            $request = $request->withHeader('X-Forwarded-For', $forwardedFor);
        }

        return $request->get('_test/client-ip')->assertOk()->getContent();
    }

    public function test_forwarded_client_is_used_behind_caddy(): void
    {
        $this->assertSame('203.0.113.5', $this->clientIp(self::CADDY, '203.0.113.5'));
    }

    public function test_without_forwarded_header_the_peer_is_the_client(): void
    {
        $this->assertSame(self::CADDY, $this->clientIp(self::CADDY));
    }

    public function test_forwarded_header_from_untrusted_peer_is_ignored(): void
    {
        $this->assertSame('198.51.100.7', $this->clientIp('198.51.100.7', '203.0.113.5'));
        $this->assertSame('10.0.0.2', $this->clientIp('10.0.0.2', '203.0.113.5'));
    }

    public function test_screenshot_limiter_is_keyed_by_forwarded_client(): void
    {
        Storage::fake('local');
        config(['app.screenshots_rate_limit' => 2]);
        /** @var TimeInterval $interval */
        $interval = IntervalFactory::create();
        $service = app(ScreenshotService::class);
        Storage::put($service->getThumbPath($interval), UploadedFile::fake()->image('t.jpg', 32, 18)->getContent());
        $url = $service->getTemporaryUrl($interval, true);

        $viaCaddy = fn (string $client) => $this->withServerVariables(['REMOTE_ADDR' => self::CADDY])
            ->withHeader('X-Forwarded-For', $client)
            ->get($url);

        $viaCaddy('203.0.113.5')->assertOk();
        $viaCaddy('203.0.113.5')->assertOk()->assertHeader('X-RateLimit-Remaining', 0);

        // a different client behind the same proxy has its own budget
        $viaCaddy('203.0.113.6')->assertOk()->assertHeader('X-RateLimit-Remaining', 1);

        $this->withoutExceptionHandling();
        try {
            $viaCaddy('203.0.113.5');
            $this->fail('The exhausted client must be throttled');
        } catch (ThrottleRequestsException $e) {
            $this->assertSame(429, $e->getStatusCode());
        }
    }
}
