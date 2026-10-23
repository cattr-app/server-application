<?php

return [
    'enabled' => env('OIDC_ENABLED', false),
    'issuer' => env('OIDC_ISSUER'),
    'client_id' => env('OIDC_CLIENT_ID'),
    'client_secret' => env('OIDC_CLIENT_SECRET'),
    'redirect_uri' => env('OIDC_REDIRECT_URI') ?: rtrim(env('APP_URL', ''), '/') . '/auth/oidc/callback',
    // Optional PEM bundle for a private organizational CA; TLS verification remains enabled.
    'ca_bundle' => env('OIDC_CA_BUNDLE'),
    'token_lifetime_minutes' => (int) env('OIDC_TOKEN_LIFETIME_MINUTES', 480),
];
