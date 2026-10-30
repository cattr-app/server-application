<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DataObjectsAndFieldsTest extends TestCase
{
    private const URI = '/api/report/universal-report/data-objects-and-fields';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_returns_fields_of_base(): void
    {
        $base = UniversalReportBase::PROJECT;

        $response = $this->actingAs($this->admin)->getJson(self::URI . "?base={$base->value}");

        $response->assertOk();
        $response->assertJsonPath('data.fields', $base->fields());
        $response->assertJsonStructure(['data' => ['fields', 'dataObjects', 'charts']]);
    }

    public function test_without_base(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertValidationError();
    }

    public function test_unknown_base(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI . '?base=unknown');

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $this->getJson(self::URI . '?base=project')->assertUnauthorized();
    }
}
