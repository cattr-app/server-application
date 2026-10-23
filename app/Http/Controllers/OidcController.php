<?php

namespace App\Http\Controllers;

use App\Auth\OidcClient;
use App\Http\Transformers\AuthTokenTransformer;
use App\Models\OidcIdentity;
use App\Models\User;
use App\Scopes\UserAccessScope;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OidcController extends Controller
{
    public function configuration(): JsonResponse
    {
        return response()->json(['enabled' => (bool) config('oidc.enabled')])
            ->header('Cache-Control', 'no-store');
    }

    public function redirect(Request $request, Factory $http): RedirectResponse|JsonResponse
    {
        if (!config('oidc.enabled')) {
            return $this->failure(404);
        }
        try {
            if (config('session.driver') === 'cookie') {
                return $this->failure(503);
            }
            $request->session()->regenerate(true);
            $request->session()->forget(['oidc', 'oidc_grant']);
            $url = (new OidcClient($request->session(), config('oidc'), $http))->authorizationUrl();
            return redirect()->away($url)->withHeaders($this->headers());
        } catch (Throwable) {
            // Provider exceptions can contain tokens, codes, or client credentials.
            return $this->failure(503);
        }
    }

    public function callback(Request $request, Factory $http): RedirectResponse|JsonResponse
    {
        if (!config('oidc.enabled')) {
            return $this->failure(404);
        }
        try {
            if ($request->query('error') !== null || !is_string($request->query('code'))
                || !is_string($request->query('state'))
                || ($request->query('iss') !== null && !is_string($request->query('iss')))) {
                throw new \RuntimeException('Invalid callback');
            }
            $claims = (new OidcClient($request->session(), config('oidc'), $http))->identity(
                $request->query('code'), $request->query('state'), $request->query('iss'),
            );
            $identity = OidcIdentity::where('identity_hash', OidcIdentity::identityHash($claims['issuer'], $claims['subject']))->first();
            if (!$identity || $identity->issuer !== $claims['issuer'] || $identity->subject !== $claims['subject']
                || !$identity->user || !$identity->user->active) {
                throw new \RuntimeException('Unlinked or inactive account');
            }
            $request->session()->regenerate(true);
            $request->session()->put('oidc_grant', ['identity_id' => $identity->id, 'expires' => time() + 60]);
            return redirect('/auth/oidc/complete')->withHeaders($this->headers());
        } catch (Throwable) {
            $request->session()->forget(['oidc', 'oidc_grant']);
            return redirect('/auth/login?sso_error=1')->withHeaders($this->headers());
        }
    }

    public function token(Request $request): JsonResponse
    {
        if (!config('oidc.enabled')) {
            return $this->failure(404);
        }
        $grant = $request->session()->pull('oidc_grant');
        $identity = $grant ? OidcIdentity::find($grant['identity_id']) : null;
        if (!$grant || $grant['expires'] < time() || !$identity
            || $identity->issuer !== config('oidc.issuer') || !$identity->user || !$identity->user->active) {
            return $this->failure(401);
        }
        return DB::transaction(function () use ($identity, $request, $grant): JsonResponse {
            // Same lock order as unlink: it cannot race with issuance of a new token.
            $user = User::withoutGlobalScope(UserAccessScope::class)->lockForUpdate()->find($identity->user_id);
            $current = OidcIdentity::find($identity->id);
            if ($grant['expires'] < time() || !$user || !$user->active || !$current
                || $current->identity_hash !== $identity->identity_hash) {
                return $this->failure(401);
            }
            Auth::guard('web')->setUser($user);
            $expires = now()->addMinutes(max(1, min(1440, (int) config('oidc.token_lifetime_minutes'))));
            $token = $user->createToken('oidc:' . Str::uuid(), ['*'], $expires)->plainTextToken;
            $request->session()->regenerateToken();
            return responder()->success(['token' => $token, 'expires' => $expires->toIso8601String()], new AuthTokenTransformer)
                ->respond()->withHeaders($this->headers());
        });
    }

    private function failure(int $status): JsonResponse
    {
        return response()->json(['message' => 'Organizational sign-in could not be completed.'], $status)
            ->withHeaders($this->headers());
    }

    private function headers(): array
    {
        return ['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer'];
    }
}
