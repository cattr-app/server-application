<?php

namespace Tests\Feature\CompanySettings;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use WithFaker;

    private const URI = 'company-settings';

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->withTokens()->asAdmin()->create();
        $this->user = UserFactory::refresh()->withTokens()->asUser()->create();
    }

    public function test_index_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->patchJson(self::URI);

        $response->assertOk();
    }

    public function test_index_wrong_params(): void
    {
        $response = $this->actingAs($this->admin)->patchJson(self::URI, [
            'timezone' => $this->faker->text,
        ]);

        $response->assertValidationError();
    }

    public function test_index_as_user(): void
    {
        $response = $this->actingAs($this->user)->patchJson(self::URI);

        $response->assertForbidden();
    }
}
