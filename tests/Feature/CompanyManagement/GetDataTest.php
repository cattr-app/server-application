<?php

namespace Tests\Feature\CompanyManagement;

use App\Models\Property;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class GetDataTest extends TestCase
{
    private const URI = 'v1/companymanagement/getData';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_get_data(): void
    {
        $response = $this->actingAs($this->admin)->get(self::URI);

        $response->assertSuccess();
    }
}
