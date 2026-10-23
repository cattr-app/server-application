<?php

namespace Tests\Support;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Session\Store;
use OpenSSLAsymmetricKey;

/** In-memory provider fixture; signatures are real, no organization is contacted. */
class OidcProvider
{
    public const ISSUER = 'https://identity.example.test/organization';

    public array $metadata;
    public array $claimOverrides = [];
    public array $headerOverrides = [];
    public array $omitClaims = [];
    public bool $badSignature = false;
    public int $requests = 0;
    public array $tokenRequest = [];
    private static ?OpenSSLAsymmetricKey $key = null;

    public function __construct(public Factory $http, public Store $session)
    {
        self::$key ??= openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->metadata = [
            'issuer' => self::ISSUER,
            'authorization_endpoint' => self::ISSUER . '/authorize',
            'token_endpoint' => self::ISSUER . '/token',
            'jwks_uri' => self::ISSUER . '/jwks',
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
        ];
        $http->preventStrayRequests();
        $http->fake(function (Request $request) use ($http) {
            $this->requests++;
            if (str_ends_with($request->url(), '/.well-known/openid-configuration')) {
                return $http->response($this->metadata);
            }
            if ($request->url() === self::ISSUER . '/token') {
                parse_str($request->body(), $this->tokenRequest);
                return $http->response(['id_token' => $this->jwt(), 'access_token' => 'test-provider-access-token']);
            }
            if ($request->url() === self::ISSUER . '/jwks') {
                $rsa = openssl_pkey_get_details(self::$key)['rsa'];
                return $http->response(['keys' => [[
                    'kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => 'test-key',
                    'n' => self::base64url($rsa['n']), 'e' => self::base64url($rsa['e']),
                ]]]);
            }
            throw new \RuntimeException('Unexpected provider request');
        });
    }

    public static function configuration(): array
    {
        return [
            'enabled' => true, 'issuer' => self::ISSUER, 'client_id' => 'test-cattr',
            'client_secret' => 'test-only-client-secret', 'redirect_uri' => 'https://cattr.example.test/auth/oidc/callback',
            'ca_bundle' => null, 'token_lifetime_minutes' => 480,
        ];
    }

    public static function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function jwt(): string
    {
        $claims = array_replace([
            'iss' => self::ISSUER, 'sub' => 'employee-123', 'aud' => 'test-cattr',
            'iat' => time(), 'exp' => time() + 300,
            'nonce' => $this->session->get('oidc.openid_connect_nonce'),
            // Email and groups intentionally are not authority to link or elevate an account.
            'email' => 'employee@example.test', 'groups' => ['administrators'],
        ], $this->claimOverrides);
        foreach ($this->omitClaims as $name) {
            unset($claims[$name]);
        }
        $header = array_replace(['alg' => 'RS256', 'kid' => 'test-key'], $this->headerOverrides);
        $body = self::base64url(json_encode($header)) . '.' . self::base64url(json_encode($claims));
        openssl_sign($body, $signature, self::$key, OPENSSL_ALGO_SHA256);
        if ($this->badSignature) {
            $signature[0] = chr(ord($signature[0]) ^ 1);
        }
        return $body . '.' . self::base64url($signature);
    }
}
