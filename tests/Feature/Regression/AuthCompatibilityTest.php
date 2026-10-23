<?php

namespace Tests\Feature\Regression;

use App\Enums\Role;
use App\Http\Controllers\PasswordResetController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthCompatibilityTest extends TestCase
{
    public function test_existing_hashed_sanctum_token_authenticates_without_reissuing_it(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMIN->value]);
        $plainTextToken = 'stage-a-existing-'.str_repeat('a', 32);

        // Reproduce a token row created before the framework upgrade rather
        // than using createToken(), which might conceal a format change.
        $id = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => $user->getMorphClass(),
            'tokenable_id' => $user->getKey(),
            'name' => 'pre-upgrade',
            'token' => hash('sha256', $plainTextToken),
            'abilities' => '["*"]',
            'last_used_at' => null,
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$id.'|'.$plainTextToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_revoked_bearer_token_is_not_accepted(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMIN->value]);
        $token = $user->createToken('stage-a-revoked')->plainTextToken;
        $user->tokens()->delete();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(self::HTTP_UNAUTHORIZED)
            ->assertJsonPath('error.code', 'authorization.unauthorized');
    }

    public function test_password_reset_routes_resolve_to_public_controllers(): void
    {
        foreach (['request', 'validate', 'process'] as $action) {
            $path = '/api/auth/password/reset/'.$action;
            $route = Route::getRoutes()->match(Request::create($path, 'POST'));
            $namedRoute = Route::getRoutes()->getByName('auth.reset.'.$action);

            $this->assertSame('auth.reset.'.$action, $route->getName());
            $this->assertSame(
                PasswordResetController::class.'@'.$action,
                $route->getActionName()
            );
            $this->assertNotContains('auth:sanctum', $route->gatherMiddleware());
            $this->assertSame($route, $namedRoute);
        }
    }
}
