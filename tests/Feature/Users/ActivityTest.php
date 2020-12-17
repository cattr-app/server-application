<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    private const URI = 'users/activity';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
    }

    public function test_update(): void
    {
        /* @var \Carbon\Carbon $lastActivity */
        $lastActivity = $this->admin->last_activity;

        $response = $this->actingAs($this->admin)->patchJson(self::URI);

        $user = User::find($this->admin->id);

        $response->assertOk();
        $this->assertNotEquals($lastActivity->toString(), $user->last_activity->toString());
        $this->assertTrue($user->online);
    }

    public function test_unauthorized(): void
    {
        $response = $this->patchJson(self::URI);

        $response->assertUnauthorized();
    }
}
