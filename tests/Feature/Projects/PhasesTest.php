<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\ProjectPhase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class PhasesTest extends TestCase
{
    private const URI = 'projects/phases';

    private Project $project;
    private ProjectPhase $phaseWithTasks;
    private ProjectPhase $emptyPhase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = ProjectFactory::create();
        $this->phaseWithTasks = $this->project->phases()->create(['name' => 'Design']);
        $this->emptyPhase = $this->project->phases()->create(['name' => 'Release']);

        foreach ([1, 2] as $_) {
            TaskFactory::refresh()->forProject($this->project)->create()
                ->update(['project_phase_id' => $this->phaseWithTasks->id]);
        }
        // A task of the project outside of any phase and a phase of another project must not be counted
        TaskFactory::refresh()->forProject($this->project)->create();
        ProjectFactory::create()->phases()->create(['name' => 'Foreign']);
    }

    public static function globalRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
        ];
    }

    public static function projectRoles(): array
    {
        return [
            'project manager' => [1],
            'project user' => [2],
            'project auditor' => [3],
        ];
    }

    #[DataProvider('globalRoles')]
    public function test_phases_as_global_role(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $this->project->id);
        $this->assertPhases($response->json('data.phases'));
    }

    #[DataProvider('projectRoles')]
    public function test_phases_as_project_member(int $projectRoleId): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();
        $user->projects()->attach($this->project->id, ['role_id' => $projectRoleId]);

        $response = $this->actingAs($user)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertOk();
        $this->assertPhases($response->json('data.phases'));
    }

    public function test_phases_as_user_outside_of_the_project(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertNotFound('page_not_found');
    }

    public function test_project_without_phases(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $project = ProjectFactory::create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . $project->id);

        $response->assertOk();
        $this->assertSame([], $response->json('data.phases'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI);

        $response->assertValidationError();
    }

    public function test_unknown_project(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $unknownId = Project::withoutGlobalScopes()->max('id') + 1;

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . $unknownId);

        $response->assertValidationError();
    }

    public function test_id_must_be_an_integer(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=abc');

        $response->assertValidationError();
    }

    private function assertPhases(array $phases): void
    {
        $this->assertCount(2, $phases);

        $byId = array_column($phases, null, 'id');
        $this->assertSame('Design', $byId[$this->phaseWithTasks->id]['name']);
        $this->assertSame(2, $byId[$this->phaseWithTasks->id]['tasks_count']);
        $this->assertSame('Release', $byId[$this->emptyPhase->id]['name']);
        $this->assertSame(0, $byId[$this->emptyPhase->id]['tasks_count']);
    }
}
