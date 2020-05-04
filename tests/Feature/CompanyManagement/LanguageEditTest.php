<?php

namespace Tests\Feature\CompanyManagement;

use App\Models\Property;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class LanguageEditTest extends TestCase
{
    private const URI = 'v1/companymanagement/language/edit';

    private User $admin;

    private function assertLanguageNotInDb(string $language): self
    {
        return $this->assertDatabaseMissing('properties', [
            'name' => 'language',
            'value' => $language
        ]);
    }

    private function assertLanguageInDb(string $languagee): self
    {
        return $this->assertDatabaseHas('properties', [
            'name' => 'language',
            'value' => $languagee
        ]);
    }

    public function test_update(): void
    {
        $this->createLanguage('jp');
        $this->assertLanguageInDb('jp');

        $requestData = ['language' => 'en'];
        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertSuccess();
        $this->assertLanguageInDb($requestData['language']);
    }

    private function createLanguage(string $language): void
    {
        Property::updateOrCreate([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'language'
        ], [
            'value' => $language
        ]);
    }

    public function test_invalid(): void
    {
        $requestData = ['language' => 'xx'];
        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertValidationError();
        $this->assertLanguageNotInDb($requestData['language']);
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }
}
