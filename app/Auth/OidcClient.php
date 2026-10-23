<?php

namespace App\Auth;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Client\Factory;
use Jumbojett\OpenIDConnectClient;
use RuntimeException;
use stdClass;

/** A request-scoped adapter: no PHP globals, native sessions, header(), or exit(). */
class OidcClient extends OpenIDConnectClient
{
    public function __construct(
        private Session $session,
        private array $configuration,
        private Factory $http,
    ) {
        foreach (['issuer', 'redirect_uri'] as $key) {
            $url = $configuration[$key] ?? '';
            $this->requireHttps($url);
            if (parse_url($url, PHP_URL_QUERY) !== null) {
                throw new RuntimeException('Invalid OIDC configuration');
            }
        }
        if (empty($configuration['client_id']) || empty($configuration['client_secret'])) {
            throw new RuntimeException('Invalid OIDC configuration');
        }
        parent::__construct($configuration['issuer'], $configuration['client_id'], $configuration['client_secret']);
        $this->setRedirectURL($configuration['redirect_uri']);
        $this->setCodeChallengeMethod('S256');
    }

    public function authorizationUrl(): string
    {
        $this->discover();
        $endpoint = $this->getProviderConfigValue('authorization_endpoint');
        $this->requireHttps($endpoint);
        $methods = $this->getProviderConfigValue('code_challenge_methods_supported', []);
        if ($methods && !in_array('S256', $methods, true)) {
            throw new RuntimeException('Provider does not support S256 PKCE');
        }
        $this->session->forget(['oidc', 'oidc_grant']);
        $nonce = $this->setNonce(bin2hex(random_bytes(32)));
        $state = $this->setState(bin2hex(random_bytes(32)));
        $verifier = $this->setCodeVerifier(bin2hex(random_bytes(32)));
        $this->session->put('oidc.transaction', [
            'started' => time(),
            'configuration' => $this->binding(),
        ]);
        return $endpoint . (str_contains($endpoint, '?') ? '&' : '?') . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->configuration['client_id'],
            'redirect_uri' => $this->configuration['redirect_uri'],
            'scope' => 'openid',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge_method' => 'S256',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Return only a cryptographically verified issuer/subject pair. */
    public function identity(string $code, string $state, ?string $responseIssuer = null): array
    {
        $transaction = $this->session->pull('oidc.transaction');
        try {
            if (!$transaction || $transaction['configuration'] !== $this->binding()
                || $transaction['started'] < time() - 300 || $transaction['started'] > time()
                || $code === '' || $state === '' || !is_string($this->getState())
                || !hash_equals($this->getState(), $state)
                || ($responseIssuer !== null && $responseIssuer !== $this->configuration['issuer'])) {
                throw new RuntimeException('Invalid OIDC transaction');
            }
            // Reject state errors before making any provider request.
            $this->discover();
            $tokens = $this->requestTokens($code);
            if (!is_object($tokens) || isset($tokens->error) || !is_string($tokens->id_token ?? null)) {
                throw new RuntimeException('Invalid OIDC token response');
            }
            $jwt = $tokens->id_token;
            if (count(explode('.', $jwt)) !== 3) {
                throw new RuntimeException('Invalid compact ID token');
            }
            $header = $this->decodeJWT($jwt);
            if (($header->alg ?? null) !== 'RS256' || !is_string($header->kid ?? null) || $header->kid === ''
                || !empty($header->crit)) {
                throw new RuntimeException('Unsupported OIDC signing algorithm');
            }
            $this->verifySignatures($jwt);
            $this->idToken = $jwt;
            $claims = $this->decodeJWT($jwt, 1);
            $this->validateClaims($claims, $transaction['started']);
            if (!$this->verifyJWTClaims($claims, $tokens->access_token ?? null)) {
                throw new RuntimeException('Invalid OIDC claims');
            }
            return ['issuer' => $claims->iss, 'subject' => $claims->sub];
        } finally {
            // Also consume failed callbacks; retrying requires a new sign-in.
            $this->session->forget('oidc');
        }
    }

    private function validateClaims(?stdClass $claims, int $started): void
    {
        $client = $this->configuration['client_id'];
        $audience = $claims->aud ?? null;
        $audiences = is_string($audience) ? [$audience] : $audience;
        if (!$claims || ($claims->iss ?? null) !== $this->configuration['issuer']
            || !is_string($claims->sub ?? null) || !preg_match('/^[\x20-\x7E]{1,255}$/D', $claims->sub)
            || !is_array($audiences) || !in_array($client, $audiences, true)
            || count(array_filter($audiences, 'is_string')) !== count($audiences)
            || (count($audiences) > 1 && ($claims->azp ?? null) !== $client)
            || (isset($claims->azp) && $claims->azp !== $client)
            || !is_string($claims->nonce ?? null) || !hash_equals($this->getNonce(), $claims->nonce)
            || !is_int($claims->exp ?? null) || $claims->exp <= time() - 60
            || !is_int($claims->iat ?? null) || $claims->iat > time() + 60
            || $claims->iat < $started - 60 || $claims->exp <= $claims->iat
            || (isset($claims->nbf) && (!is_int($claims->nbf) || $claims->nbf > time() + 60))) {
            throw new RuntimeException('Invalid OIDC claims');
        }
    }

    private function discover(): void
    {
        if ($this->getWellKnownIssuer() !== $this->configuration['issuer']) {
            throw new RuntimeException('OIDC discovery issuer mismatch');
        }
    }

    private function binding(): array
    {
        return array_intersect_key($this->configuration, array_flip(['issuer', 'client_id', 'redirect_uri']));
    }

    private function requireHttps(string $url): void
    {
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || !parse_url($url, PHP_URL_HOST)
            || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null
            || parse_url($url, PHP_URL_FRAGMENT) !== null) {
            throw new RuntimeException('OIDC requires HTTPS URLs without credentials or fragments');
        }
    }

    protected function fetchURL(string $url, string $post_body = null, array $headers = [])
    {
        $this->requireHttps($url);
        $request = $this->http->withOptions([
            'allow_redirects' => false,
            'verify' => $this->configuration['ca_bundle'] ?: true,
        ])->connectTimeout(5)->timeout(10)->acceptJson();
        foreach ($headers as $header) {
            [$name, $value] = explode(':', $header, 2);
            $request = $request->withHeaders([$name => trim($value)]);
        }
        $response = $post_body === null ? $request->get($url)
            : $request->withBody($post_body, 'application/x-www-form-urlencoded')->post($url);
        if ($response->status() !== 200 || strlen($response->body()) > 1048576) {
            throw new RuntimeException('OIDC provider request failed');
        }
        return $response->body();
    }

    protected function getSessionKey(string $key)
    {
        return $this->session->get('oidc.' . $key, false);
    }

    protected function setSessionKey(string $key, $value)
    {
        $this->session->put('oidc.' . $key, $value);
    }

    protected function unsetSessionKey(string $key)
    {
        $this->session->forget('oidc.' . $key);
    }
}
