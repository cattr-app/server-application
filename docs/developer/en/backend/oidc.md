# Organizational sign-in with OpenID Connect

Cattr can optionally authenticate **existing active employee and administrator accounts**
against one organizational OpenID Connect provider. Cattr continues to own authorization:
an account's role, project membership, and tracking settings do not change during sign-in.
The feature is disabled by default. Local password sign-in remains available.

This implementation uses Authorization Code flow, S256 PKCE and RS256-signed ID tokens.
It does not implement SAML, automatic user provisioning, email-based account linking,
group-to-role synchronization, or provider-initiated/back-channel logout.

## Configure the provider

1. Register a **confidential web client** at your organization's OIDC provider. Enable
   Authorization Code flow and S256 PKCE. Use `client_secret_basic` or `client_secret_post`
   client authentication and RS256 ID tokens. Implicit and password grants are not used.
2. Register the **exact** redirect URI, for example:
   `https://time.example.org/auth/oidc/callback`. No wildcard callback is needed.
3. Set the following server configuration through your normal secret-management process:

   ```dotenv
   APP_URL=https://time.example.org
   SESSION_SECURE_COOKIE=true
   SESSION_DRIVER=file
   SESSION_BLOCK_STORE=file
   OIDC_ENABLED=true
   OIDC_ISSUER=https://identity.example.org/realms/company
   OIDC_CLIENT_ID=cattr
   OIDC_CLIENT_SECRET=<confidential-client-secret>
   OIDC_TOKEN_LIFETIME_MINUTES=480
   ```

   `OIDC_REDIRECT_URI` defaults to `APP_URL/auth/oidc/callback`; set it explicitly if
   necessary. The SPA, callback, and token exchange must use the **same HTTPS origin**.
   Split-origin frontends are not supported by this browser-session handoff.

   When TLS terminates at a reverse proxy, configure Laravel's
   `trustedproxy.proxies` for the exact trusted proxy hops and ensure that they
   forward the original HTTPS scheme. `APP_URL` alone does not make an incoming
   proxied HTTP request secure. Verify both successful and failed callback
   redirects remain on the configured HTTPS origin; do not trust arbitrary
   client-supplied forwarding headers.

4. Apply the `oidc_identities` migration with the normal release migration procedure.
   Clear/rebuild Laravel's configuration cache after changing configuration.
5. Ensure the issuer's discovery document, token endpoint and JWKS are reachable from
   the application. HTTPS certificate and hostname verification are mandatory. For an
   internal organizational CA, set `OIDC_CA_BUNDLE` to a readable PEM CA bundle. There is
   no option to disable TLS verification or follow provider HTTP redirects.

The PHP dependencies include `jumbojett/openid-connect-php` (Apache-2.0). Its signature
validation uses the existing `phpseclib/phpseclib` dependency; the minimum supported
version is raised to 3.0.57. The PHP cURL extension is required by that dependency.
The application adapter uses Laravel's HTTP client for transport so it can enforce
timeouts, verified TLS and no redirects. The library's global `$_REQUEST`, native PHP
session, and `header()/exit()` authentication path are not used, including under Octane.

### Multiple application nodes

Use a shared server-side session driver and a shared lock-capable cache store, for
example `SESSION_DRIVER=redis` and `SESSION_BLOCK_STORE=redis`. Cookie-only sessions
are rejected because callback consumption must be coordinated on the server.
The existing desktop-key cache uses the `octane` store: retain the existing deployment's
desktop-key routing/stickiness requirements when using multiple workers or nodes.
Deploy desktop issuance and exchange changes together. Pending desktop grants
are held in the existing process-local Octane cache and are invalidated by its
restart; do not mix worker versions while relying on outstanding grants.

## Explicitly link an existing account

Create or identify the Cattr user through the existing administration interface, assign
the appropriate role, and independently verify that person's **subject (`sub`) at the
configured issuer** through the identity administrator. Do not infer a subject from an
email address or accept a subject supplied by an unauthenticated person.

```bash
php artisan cattr:oidc:link 42 verified-subject-from-identity-provider
```

Both the exact issuer and the case-sensitive subject identify the account. The command
is idempotent for the same binding; it refuses to replace an existing binding or reuse
an identity for a different account. There is at most one OIDC identity per Cattr user.
It does not create users, change passwords, or grant administrator privileges. Even a
verified matching email or an IdP `administrators` group does not link or elevate an account.

Users can now choose **Sign in with your organization** on Cattr's login page. Account
deactivation, soft deletion, and unlinking are checked before a Cattr token is issued.

## Desktop application

After organizational web sign-in, use Cattr's existing **Desktop application login**
page (`/desktop-login`) to hand off to the installed client through `cattr://authenticate`.
The HTTP response envelope and existing desktop protocol remain compatible with old
clients; the web frontend accepts both wrapped and legacy flat desktop responses.
No organization password or IdP access/refresh token is given to the desktop client.

For an OIDC-derived session, the desktop key is one-use and IP-bound, and the resulting
Cattr desktop token **inherits the original SSO token's expiry**, rather than extending
the session. Unlinking also invalidates a pending OIDC desktop handoff. Legacy
password-derived desktop handoffs retain their previous behavior.

Automated tests cover the server-side handoff and frontend response handling. A real
provider/browser/desktop protocol-handler test is still required for the target client
release and deployment, including reauthentication when the Cattr token expires.

## Token lifecycle and offboarding

The authorization transaction lasts at most five minutes. The verified callback rotates
the browser session and creates a session-bound grant valid for 60 seconds. A CSRF-protected
POST consumes this grant once and returns the normal Cattr API response. An API bearer token
or IdP token is never included in the OIDC redirect URL. State, nonce, issuer, audience,
authorized party, signature, issued-at, not-before and expiry are validated.

`OIDC_TOKEN_LIFETIME_MINUTES` defaults to eight hours, bounded to 1–1440 minutes. Both web
and derived desktop Cattr tokens are bounded by this expiry. Cattr logout revokes its own
token; it does **not** end the organization's IdP session. Conversely, IdP logout or IdP-only
account disabling does **not** immediately revoke previously issued Cattr tokens.

For immediate Cattr offboarding, disable the Cattr account and revoke its tokens using
your established administrative procedure, or explicitly unlink it:

```bash
php artisan cattr:oidc:link 42 --unlink
```

Unlinking revokes **all** Cattr tokens for that user, including password-derived tokens.
It does not delete the Cattr user, work records, project memberships, or password. Disabling
`OIDC_ENABLED` stops new OIDC sign-ins/handoffs; existing bearer tokens retain their expiry
unless separately revoked. This optional feature does not enforce SSO-only access.

## HTTP entry points

| Method | Path | Behavior |
| --- | --- | --- |
| GET | `/auth/oidc/config` | Public `{"enabled":false}`/`true` flag; no issuer or client secrets |
| GET | `/auth/oidc/redirect` | Start a fresh browser-bound authorization transaction |
| GET | `/auth/oidc/callback` | Verify provider response; redirect to fixed `/auth/oidc/complete` |
| POST | `/auth/oidc/token` | Session cookie plus CSRF token; consume grant and return API token/user |

Disabled authentication endpoints return 404. Configuration/provider startup failures
return a generic 503; callback failures return to `/auth/login?sso_error=1` without
echoing provider errors. Invalid/consumed/expired browser grants return 401; CSRF failures
return 419. Responses carrying authentication data or redirects use `Cache-Control:
no-store` and `Referrer-Policy: no-referrer`. No caller-controlled return URL is accepted.
Web-server, reverse-proxy and observability configuration must omit/redact query strings
for the OIDC callback, which necessarily receives an authorization code from the provider.
Do not log client secrets, authorization codes, ID/access tokens, or full provider errors.

## Validation and rollback

The focused tests use an isolated in-memory SQLite schema and an in-memory mock IdP with
real RSA signatures; they never connect to an organization's directory or live database:

```bash
vendor/bin/phpunit tests/Unit/OidcClientTest.php tests/Feature/Auth/OidcTest.php
pnpm test:oidc-frontend
```

They exercise the real web/session/CSRF middleware, account binding, role preservation,
replay rejection, expired grants, invalid tokens, unlink/revocation, the existing desktop
wire protocol, response envelopes, and migration rollback. They do not substitute for a
real-provider acceptance test or the application's full MySQL-dependent legacy suite.

Before enabling in production, back up the database and test employee/admin login,
desktop opening and expiry, offboarding, private-CA trust, and the local emergency
administrator account in a staging deployment. Do not remove the emergency login path.

To roll back, disable OIDC, revoke affected Cattr tokens as required, restore the prior
application image and follow the normal migration rollback procedure. The migration's
`down()` drops only the identity-binding table; it preserves users and work data. Retain
an approved protected backup of bindings if a later reinstatement is intended.
