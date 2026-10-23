<?php

namespace App\Auth;

use App\Exceptions\Entities\AuthorizationException;
use App\Http\Transformers\AuthTokenTransformer;
use App\Models\OidcIdentity;
use App\Models\User;
use App\Scopes\UserAccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/** Preserve the existing desktop wire protocol without extending an SSO session. */
class OidcDesktopGrant
{
    public function forUser(User $user): ?array
    {
        $token = $user->currentAccessToken();
        if (!$token instanceof PersonalAccessToken || !str_starts_with($token->name, 'oidc:')) {
            return null;
        }
        $identity = OidcIdentity::where('user_id', $user->id)->where('issuer', config('oidc.issuer'))->first();
        if (!config('oidc.enabled') || !$identity || !$token->expires_at || $token->expires_at->isPast()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }
        return [
            'kind' => 'oidc', 'user_id' => $user->id, 'identity_id' => $identity->id,
            'source_token_id' => $token->getKey(),
            'expires_at' => $token->expires_at->toIso8601String(),
        ];
    }

    public function exchange(string $key): JsonResponse
    {
        return Cache::store(config('session.block_store', 'file'))->lock('oidc-desktop:' . hash('sha256', $key), 90)
            ->block(10, function () use ($key): JsonResponse {
                // Consume under a shared lock before waiting for any database locks.
                $grant = Cache::store('octane')->pull($key);
                if (!config('oidc.enabled') || !is_array($grant) || ($grant['kind'] ?? null) !== 'oidc'
                    || !is_int($grant['source_token_id'] ?? null) || $grant['source_token_id'] < 1
                    || Carbon::parse($grant['expires_at'])->isPast()) {
                    throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
                }
                return DB::transaction(function () use ($grant): JsonResponse {
                    $user = User::withoutGlobalScope(UserAccessScope::class)->lockForUpdate()->find($grant['user_id']);
                    $identity = OidcIdentity::find($grant['identity_id']);
                    $source = PersonalAccessToken::query()->lockForUpdate()->find($grant['source_token_id']);
                    $expires = Carbon::parse($grant['expires_at']);
                    if (!$user || !$user->active || !$identity || $identity->user_id != $user->id
                        || $identity->issuer !== config('oidc.issuer') || $expires->isPast()
                        || !$source || $source->tokenable_type !== $user->getMorphClass()
                        || (int) $source->tokenable_id !== $user->id || !str_starts_with($source->name, 'oidc:')
                        || !$source->expires_at || $source->expires_at->isPast()) {
                        throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
                    }
                    $expires = $expires->min($source->expires_at);
                    Auth::guard('web')->setUser($user);
                    $token = $user->createToken('oidc:desktop:' . Str::uuid(), ['*'], $expires)->plainTextToken;
                    return responder()->success(['token' => $token, 'expires' => $expires->toIso8601String()], new AuthTokenTransformer)->respond();
                });
            });
    }
}
