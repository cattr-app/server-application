<?php

namespace Tests\Feature\Rules;

use App\Models\Factories\UserFactory;
use App\Models\Rule;
use Tests\TestCase;

class CountTest extends TestCase
{
    const URI = 'v1/rules/count';

    private $admin;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = app(UserFactory::class)
            ->withTokens()
            ->asUser()
            ->create();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();
    }

    public function test_count()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);
        $response->assertOk();
        $response->assertJson(['total' => Rule::count()]);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);
        $response->assertError(401);
    }

    public function test_forbidden()
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertStatus(403);
    }
}
