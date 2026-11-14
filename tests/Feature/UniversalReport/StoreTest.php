<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\UniversalReport;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class StoreTest extends TestCase
{
    private const URI = '/api/report/universal-report';

    private int $projectId;
    private int $taskId;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectId = ProjectFactory::refresh()->create()->id;
        $this->taskId = TaskFactory::refresh()->create()->id;
        $this->userId = UserFactory::refresh()->asUser()->create()->id;
    }

    private function payload(array $data = []): array
    {
        return $data + [
            'name' => 'Weekly overview',
            'type' => UniversalReportType::PERSONAL->value,
            'base' => UniversalReportBase::PROJECT->value,
            'dataObjects' => [$this->projectId],
            'fields' => ['base' => ['name', 'description'], 'calculations' => ['total_spent_time']],
            'charts' => ['total_spent_time_day'],
        ];
    }

    private function store(User $user, array $data = []): TestResponse
    {
        return $this->actingAs($user)->postJson(self::URI, $this->payload($data));
    }

    private function user(string $role): User
    {
        return UserFactory::refresh()->$role()->withTokens()->create();
    }

    public static function allRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    public static function nonAdminRoles(): array
    {
        return [
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('allRoles')]
    public function test_role_saves_personal_report(string $role): void
    {
        $user = $this->user($role);

        $response = $this->store($user);

        $response->assertOk()->assertJsonStructure(['data' => ['message', 'id']]);
        $this->assertDatabaseHas('universal_reports', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
            'name' => 'Weekly overview',
            'type' => 'personal',
            'base' => 'project',
        ]);
    }

    public function test_admin_saves_company_report(): void
    {
        $admin = $this->user('asAdmin');

        $response = $this->store($admin, ['type' => UniversalReportType::COMPANY->value]);

        $response->assertOk();
        $this->assertDatabaseHas('universal_reports', [
            'id' => $response->json('data.id'),
            'user_id' => $admin->id,
            'type' => 'company',
        ]);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_save_company_report(string $role): void
    {
        $response = $this->store($this->user($role), ['type' => UniversalReportType::COMPANY->value]);

        $response->assertError(403);
        $this->assertDatabaseMissing('universal_reports', ['name' => 'Weekly overview']);
    }

    public function test_settings_are_stored_as_sent(): void
    {
        $response = $this->store($this->user('asUser'));

        $report = UniversalReport::findOrFail($response->json('data.id'));
        $this->assertSame([$this->projectId], $report->data_objects);
        $this->assertSame(
            ['base' => ['name', 'description'], 'calculations' => ['total_spent_time']],
            $report->fields
        );
        $this->assertSame(['total_spent_time_day'], $report->charts);
    }

    public static function invalidFields(): array
    {
        return [
            'unknown section' => [['nonsense_section' => ['name']]],
            'field of another section' => [['base' => ['task_name']]],
            'unknown field' => [['base' => ['nonsense']]],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_fields_are_validated_against_their_section(array $fields): void
    {
        $this->store($this->user('asUser'), ['fields' => $fields])->assertValidationError();
        $this->assertDatabaseMissing('universal_reports', ['name' => 'Weekly overview']);
    }

    public function test_empty_charts_are_stored(): void
    {
        $response = $this->store($this->user('asUser'), ['charts' => []]);

        $response->assertOk();
        $this->assertSame([], UniversalReport::findOrFail($response->json('data.id'))->charts);
    }

    public function test_charts_are_required(): void
    {
        $payload = $this->payload();
        unset($payload['charts']);

        $this->actingAs($this->user('asUser'))->postJson(self::URI, $payload)->assertValidationError();

        $this->assertDatabaseMissing('universal_reports', ['name' => 'Weekly overview']);
    }

    public function test_saved_report_can_be_read_back(): void
    {
        $user = $this->user('asUser');
        $id = $this->store($user)->json('data.id');

        $this->actingAs($user)->getJson(self::URI . "/show?id=$id")
            ->assertOk()
            ->assertJsonPath('data.name', 'Weekly overview')
            ->assertJsonPath('data.user_id', $user->id);
    }

    public static function bases(): array
    {
        return [
            'project' => ['project', 'projectId', 'base', 'name'],
            'user' => ['user', 'userId', 'base', 'full_name'],
            'task' => ['task', 'taskId', 'base', 'task_name'],
        ];
    }

    #[DataProvider('bases')]
    public function test_each_base_is_saved_with_its_own_data_objects(
        string $base,
        string $objectProperty,
        string $section,
        string $field
    ): void {
        $response = $this->store($this->user('asAdmin'), [
            'base' => $base,
            'dataObjects' => [$this->$objectProperty],
            'fields' => [$section => [$field]],
            'charts' => ['total_spent_time_day'],
        ]);

        $response->assertOk();
        $report = UniversalReport::findOrFail($response->json('data.id'));
        $this->assertSame(UniversalReportBase::from($base), $report->base);
        $this->assertSame([$this->$objectProperty], $report->data_objects);
    }

    public function test_data_objects_must_belong_to_the_base(): void
    {
        $response = $this->store($this->user('asAdmin'), [
            'base' => UniversalReportBase::USER->value,
            'dataObjects' => [0],
            'fields' => ['base' => ['full_name']],
        ]);

        $response->assertValidationError();
        $this->assertDatabaseMissing('universal_reports', ['name' => 'Weekly overview']);
    }

    public static function invalidPayloads(): array
    {
        return [
            'no name' => [['name' => null]],
            'name is an array' => [['name' => ['a']]],
            'no type' => [['type' => null]],
            'unknown type' => [['type' => 'shared']],
            'no fields' => [['fields' => null]],
            'fields is a string' => [['fields' => 'name']],
            'section is a string' => [['fields' => ['base' => 'name']]],
            'unknown field' => [['fields' => ['base' => ['nonexistent']]]],
            'no dataObjects' => [['dataObjects' => null]],
            'dataObjects is a number' => [['dataObjects' => 1]],
            'unknown data object' => [['dataObjects' => [0]]],
            'data object is not an id' => [['dataObjects' => ['abc']]],
            'charts is null' => [['charts' => null]],
            'charts is a string' => [['charts' => 'total_spent_time_day']],
            'unknown chart' => [['charts' => ['nonexistent']]],
            'chart of another base' => [['charts' => ['total_spent_time_day_and_tasks']]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation(array $override): void
    {
        $response = $this->store($this->user('asAdmin'), $override);

        $response->assertValidationError();
        $this->assertDatabaseMissing('universal_reports', ['name' => 'Weekly overview']);
    }

    public static function invalidBases(): array
    {
        return [
            'no base' => [null],
            'unknown base' => ['group'],
        ];
    }

    #[DataProvider('invalidBases')]
    public function test_unusable_base_is_rejected(?string $base): void
    {
        $response = $this->store($this->user('asAdmin'), ['base' => $base]);

        $response->assertStatus(self::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertDatabaseMissing('universal_reports', ['name' => 'Weekly overview']);
    }

    public function test_unauthorized(): void
    {
        $this->postJson(self::URI, $this->payload())->assertUnauthorized();
    }
}
