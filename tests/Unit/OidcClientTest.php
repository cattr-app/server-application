<?php

namespace Tests\Unit;

use App\Auth\OidcClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\OidcProvider;

class OidcClientTest extends TestCase
{
    private Store $session;
    private Factory $http;
    private OidcProvider $provider;

    protected function setUp(): void
    {
        $this->session = new Store('oidc-test', new ArraySessionHandler(120));
        $this->session->start();
        $this->http = new Factory();
        $this->provider = new OidcProvider($this->http, $this->session);
    }

    private function client(array $override = []): OidcClient
    {
        return new OidcClient($this->session, array_replace(OidcProvider::configuration(), $override), $this->http);
    }

    private function begin(): array
    {
        parse_str(parse_url($this->client()->authorizationUrl(), PHP_URL_QUERY), $parameters);
        return $parameters;
    }

    public function test_code_flow_checks_real_signature_and_sends_s256_verifier(): void
    {
        $parameters = $this->begin();
        $this->assertSame('S256', $parameters['code_challenge_method']);
        $this->assertSame('code', $parameters['response_type']);
        $this->assertSame('openid', $parameters['scope']);
        $this->assertSame('https://cattr.example.test/auth/oidc/callback', $parameters['redirect_uri']);
        $this->assertArrayNotHasKey('client_secret', $parameters);
        $identity = $this->client()->identity('test-code', $parameters['state'], OidcProvider::ISSUER);
        $this->assertSame(['issuer' => OidcProvider::ISSUER, 'subject' => 'employee-123'], $identity);
        $this->assertSame($parameters['code_challenge'], OidcProvider::base64url(hash('sha256', $this->provider->tokenRequest['code_verifier'], true)));
        $this->assertFalse($this->session->has('oidc'));
    }

    public function test_state_is_rejected_before_any_callback_network_request(): void
    {
        $this->begin();
        $before = $this->provider->requests;
        try {
            $this->client()->identity('test-code', 'another-browser');
            $this->fail('Invalid state accepted');
        } catch (\RuntimeException) {
            $this->assertSame($before, $this->provider->requests);
            $this->assertFalse($this->session->has('oidc'));
        }
    }

    public function test_callback_cannot_be_replayed(): void
    {
        $p = $this->begin();
        $this->client()->identity('test-code', $p['state']);
        $before = $this->provider->requests;
        $this->expectException(\RuntimeException::class);
        try {
            $this->client()->identity('test-code', $p['state']);
        } finally {
            $this->assertSame($before, $this->provider->requests);
        }
    }

    public function test_expired_transaction_is_consumed_before_network(): void
    {
        $p = $this->begin();
        $this->session->put('oidc.transaction.started', time() - 301);
        $this->expectException(\RuntimeException::class);
        $this->client()->identity('test-code', $p['state']);
    }

    public function test_changed_configuration_cannot_complete_old_transaction(): void
    {
        $p = $this->begin();
        $this->expectException(\RuntimeException::class);
        $this->client(['client_id' => 'another-client'])->identity('test-code', $p['state']);
    }

    public function test_discovery_must_match_exact_configured_issuer(): void
    {
        $this->provider->metadata['issuer'] .= '/';
        $this->expectException(\RuntimeException::class);
        $this->begin();
    }

    public function test_bad_signature_is_rejected(): void
    {
        $p = $this->begin();
        $this->provider->badSignature = true;
        $this->expectException(\Jumbojett\OpenIDConnectClientException::class);
        $this->client()->identity('test-code', $p['state']);
    }

    #[DataProvider('invalidClaims')]
    public function test_invalid_claims_are_rejected(array $overrides, array $omitted = []): void
    {
        $p = $this->begin();
        $this->provider->claimOverrides = $overrides;
        $this->provider->omitClaims = $omitted;
        $this->expectException(\RuntimeException::class);
        $this->client()->identity('test-code', $p['state']);
    }

    public static function invalidClaims(): array
    {
        return [
            'issuer' => [['iss' => 'https://other.example.test']],
            'audience' => [['aud' => 'another-client']],
            'multiple audiences without azp' => [['aud' => ['test-cattr', 'other']]],
            'non-string audience element' => [['aud' => ['test-cattr', 123], 'azp' => 'test-cattr']],
            'wrong azp' => [['azp' => 'another-client']],
            'nonce' => [['nonce' => 'another-browser']],
            'missing nonce' => [[], ['nonce']],
            'missing expiry' => [[], ['exp']],
            'missing issued at' => [[], ['iat']],
            'expired' => [['exp' => 1]],
            'issued in future' => [['iat' => PHP_INT_MAX]],
            'empty subject' => [['sub' => '']],
            'oversize subject' => [['sub' => str_repeat('a', 256)]],
            'non-string subject' => [['sub' => 123]],
            'control character in subject' => [['sub' => "employee\0another"]],
            'future not-before' => [['nbf' => PHP_INT_MAX]],
            'non-integer not-before' => [['nbf' => 'tomorrow']],
        ];
    }

    public function test_algorithm_confusion_is_rejected(): void
    {
        $p = $this->begin();
        $this->provider->headerOverrides = ['alg' => 'HS256'];
        $this->expectException(\RuntimeException::class);
        $this->client()->identity('test-code', $p['state']);
    }

    public function test_unknown_critical_header_is_rejected(): void
    {
        $p = $this->begin();
        $this->provider->headerOverrides = ['crit' => ['unknown-extension']];
        $this->expectException(\RuntimeException::class);
        $this->client()->identity('test-code', $p['state']);
    }

    public function test_multiple_audiences_with_matching_authorized_party_are_supported(): void
    {
        $p = $this->begin();
        $this->provider->claimOverrides = ['aud' => ['test-cattr', 'other'], 'azp' => 'test-cattr'];
        $this->assertSame('employee-123', $this->client()->identity('test-code', $p['state'])['subject']);
    }

    public function test_callback_response_issuer_is_checked_before_network(): void
    {
        $p = $this->begin();
        $before = $this->provider->requests;
        $this->expectException(\RuntimeException::class);
        try {
            $this->client()->identity('test-code', $p['state'], 'https://other.example.test');
        } finally {
            $this->assertSame($before, $this->provider->requests);
        }
    }

    public function test_plain_pkce_is_never_used(): void
    {
        $this->provider->metadata['code_challenge_methods_supported'] = ['plain'];
        $this->expectException(\RuntimeException::class);
        $this->begin();
    }

    public function test_http_provider_endpoint_is_rejected(): void
    {
        $this->provider->metadata['authorization_endpoint'] = 'http://identity.example.test/authorize';
        $this->expectException(\RuntimeException::class);
        $this->begin();
    }

    public function test_no_php_global_request_or_native_session_is_consumed(): void
    {
        $previous = $_REQUEST;
        try {
            $_REQUEST = ['code' => 'untrusted-code', 'error' => 'untrusted-error'];
            $p = $this->begin();
            $this->client()->identity('test-code', $p['state']);
            $this->assertSame(['code' => 'untrusted-code', 'error' => 'untrusted-error'], $_REQUEST);
            $this->assertSame(PHP_SESSION_NONE, session_status());
        } finally {
            $_REQUEST = $previous;
        }
    }
}
