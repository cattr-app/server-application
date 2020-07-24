<?php

namespace Tests\Feature\Rules;

use App\Models\Rule;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'rules/list';

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(Rule::all()->toArray());
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_forbidden(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertForbidden();
    }
}
