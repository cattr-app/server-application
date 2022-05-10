<?php
return [
    'ttl' => env('RECAPTCHA_TTL', 3600),
    'enabled' => env('RECAPTCHA_ENABLED', false),
    'site_key' => env('RECAPTCHA_SITE_KEY', ''),
    'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    'google_url' => env('RECAPTCHA_GOOGLE_URL', 'https://www.google.com/recaptcha/api/siteverify'),
    'failed_attempts' => env('RECAPTCHA_FAILED_ATTEMPTS', 10),
    'ban_attempts' => env('RECAPTCHA_BAN_ATTEMPTS', 10),
    'rate_limiter_enabled' => env('RATE_LIMITER_ENABLED', false),
    'rate_limiter_ttl' => env('RATE_LIMITER_TTL', 3600),
];
