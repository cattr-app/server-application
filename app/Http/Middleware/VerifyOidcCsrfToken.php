<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\TokenMismatchException;

/** Keep the OIDC protocol response independent of Cattr's legacy error serializer. */
class VerifyOidcCsrfToken extends VerifyCsrfToken
{
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException) {
            return response()->json(['message' => 'CSRF token mismatch.'], 419)
                ->header('Cache-Control', 'no-store')
                ->header('Referrer-Policy', 'no-referrer');
        }
    }
}
