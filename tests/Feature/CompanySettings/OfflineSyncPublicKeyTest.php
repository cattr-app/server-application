<?php

namespace Tests\Feature\CompanySettings;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use phpseclib4\Crypt\RSA;
use phpseclib4\Crypt\RSA\PublicKey;
use Settings;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class OfflineSyncPublicKeyTest extends TestCase
{
    private const URI = 'offline-sync/public-key';

    public static function roles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('roles')]
    public function test_public_key(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['key']]);
        $response->assertJsonPath('data.key', Settings::scope('core.offline-sync')->get('public_key'));
    }

    public function test_key_is_a_usable_rsa_public_key(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();

        $key = $this->actingAs($user)->getJson(self::URI)->json('data.key');

        $this->assertStringStartsWith('-----BEGIN PUBLIC KEY-----', $key);
        $this->assertInstanceOf(PublicKey::class, RSA::load($key));
    }

    public function test_key_is_null_when_not_stored(): void
    {
        DB::table('settings')->where(['module_name' => 'core.offline-sync', 'key' => 'public_key'])->delete();
        $user = UserFactory::asUser()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJsonPath('data.key', null);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
