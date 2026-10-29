<?php

namespace Tests\Feature\Console;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings;
use Tests\TestCase;

class RegisterInstanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Settings::scope('core')->set('instance', '');
        config(['app.stats_collector_url' => 'https://stats.example.test']);
    }

    public function test_successful_registration_stores_instance_id(): void
    {
        Http::fake(['*' => Http::response([
            'instanceId' => 'registered-instance',
            'release' => ['flashMessage' => 'Instance registered'],
        ])]);

        $this->artisan('cattr:register', ['adminEmail' => 'admin@example.test'])
            ->expectsOutputToContain('Instance registered')
            ->assertExitCode(0);

        $this->assertSame('registered-instance', Settings::scope('core')->get('instance'));
        Http::assertSent(static fn (Request $request) =>
            $request->url() === 'https://stats.example.test/v2/instance'
            && $request['ownerEmail'] === 'admin@example.test'
            && $request['version'] === config('app.version')
        );
    }

    public function test_http_error_displays_json_message(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Registration rejected'], 422)]);

        $this->artisan('cattr:register', ['adminEmail' => 'admin@example.test'])
            ->expectsOutputToContain('Registration rejected')
            ->assertExitCode(0);

        $this->assertSame('', Settings::scope('core')->get('instance'));
    }

    public static function unusableErrorBodies(): array
    {
        return [
            'empty body' => [''],
            'malformed JSON' => ['{invalid'],
            'missing message' => ['{"error":"unavailable"}'],
            'empty message' => ['{"message":""}'],
            'non-string message' => ['{"message":{"details":"unavailable"}}'],
        ];
    }

    #[DataProvider('unusableErrorBodies')]
    public function test_http_error_without_usable_message_falls_back_to_diagnostic(string $body): void
    {
        Http::fake(['*' => Http::response($body, 503, ['Content-Type' => 'application/json'])]);

        $this->artisan('cattr:register', ['adminEmail' => 'admin@example.test'])
            ->expectsOutputToContain('503')
            ->assertExitCode(0);

        $this->assertSame('', Settings::scope('core')->get('instance'));
    }

    public function test_connection_failure_is_handled(): void
    {
        Http::fake(static fn () => throw new ConnectionException('Connection refused'));

        $this->artisan('cattr:register', ['adminEmail' => 'admin@example.test'])
            ->expectsOutputToContain('Could not get a response from the server')
            ->assertExitCode(0);
    }

    public function test_invalid_url_exception_is_handled(): void
    {
        Http::fake(static fn () => throw new InvalidArgumentException('Invalid URL'));

        $this->artisan('cattr:register', ['adminEmail' => 'admin@example.test'])
            ->expectsOutputToContain('Could not get a response from the server')
            ->assertExitCode(0);
    }

    public static function invalidSuccessBodies(): array
    {
        return [
            'empty body' => [''],
            'malformed JSON' => ['{invalid'],
            'JSON scalar' => ['"unexpected"'],
        ];
    }

    #[DataProvider('invalidSuccessBodies')]
    public function test_invalid_success_response_is_reported_without_registering(string $body): void
    {
        Http::fake(['*' => Http::response($body, 200, ['Content-Type' => 'application/json'])]);

        $this->artisan('cattr:register', ['adminEmail' => 'admin@example.test'])
            ->expectsOutputToContain('Could not parse the registration response')
            ->assertExitCode(0);

        $this->assertSame('', Settings::scope('core')->get('instance'));
    }

    public function test_already_registered_instance_does_not_send_request(): void
    {
        Settings::scope('core')->set('instance', 'existing-instance');
        Http::fake();

        $this->artisan('cattr:register', ['adminEmail' => 'admin@example.test'])
            ->assertExitCode(1);

        Http::assertNothingSent();
    }
}
