<?php

namespace Tests\Feature\UniversalReport;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class BasesTest extends TestCase
{
    private const URI = '/api/report/universal-report/bases';

    public static function allRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('allRoles')]
    public function test_role_gets_the_bases(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI);

        $response->assertOk();
        $this->assertSame(['project', 'user', 'task'], $response->json('data'));
    }

    public function test_unauthorized(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }
}
