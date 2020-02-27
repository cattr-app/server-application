<?php

namespace Modules\CompanyManagement\Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TimeZoneEditTest extends TestCase
{
    private const URI = 'v1/companymanagement/timezone/edit';

    private User $admin;

    private function assertTimeZoneInDb(string $timezone): self
    {
        return $this->assertDatabaseHas('properties', [
            'name' => 'timezone',
            'value' => $timezone
        ]);
    }

    private function assertTimeZoneNotInDb(string $timezone): self
    {
        return $this->assertDatabaseMissing('properties', [
            'name' => 'timezone',
            'value' => $timezone
        ]);
    }

    private function createTimeZone(string $timezone): void
    {
        Property::create([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'timezone',
            'value' => $timezone
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_create(): void
    {
        $requestData = ['timezone' => 'UTC'];
        $this->assertTimeZoneNotInDb($requestData['timezone']);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertSuccess();
        $this->assertTimeZoneInDb($requestData['timezone']);
    }

    public function test_update(): void
    {
        $this->createTimeZone('Europe/London');
        $this->assertTimeZoneInDb('Europe/London');

        $requestData = ['timezone' => 'UTC'];
        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertSuccess();
        $this->assertTimeZoneInDb($requestData['timezone']);
    }

    public function test_invalid(): void
    {
        $requestData = ['timezone' => 'Asia/Nowhere'];
        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertValidationError();
        $this->assertTimeZoneNotInDb($requestData['timezone']);
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
