<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use App\Services\ProjectMemberService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Seeder;

/**
 * Class UsersTableSeeder
 */
class DemoDataSeeder extends Seeder
{
    private const INTERVAL_DURATION_MINUTES = 5;
    private const INTERVALS_AMOUNT = 30;
    private const TASKS_AMOUNT = 10;
    private const PROJECTS_AMOUNT = 1;

    private ProjectMemberService $projectMemberService;

    public function __construct(ProjectMemberService $service)
    {
        $this->projectMemberService = $service;
    }

    private function createTasks($user): Factory
    {
        return Task::factory()->for($user, 'assigned')->hasAttached($user)->has(
            call_user_func_array(
                [TimeInterval::factory()->for($user)->count(self::INTERVALS_AMOUNT)->withScreenshot(), 'sequence'],
                array_map(
                    static function ($key) {
                        return [
                            'start_at' => now()->subMinutes(self::INTERVAL_DURATION_MINUTES * ($key + 1))->toDateTimeString(),
                            'end_at' => now()->subMinutes(self::INTERVAL_DURATION_MINUTES * $key)->toDateTimeString()
                        ];
                    },
                    array_keys(array_fill(0, self::INTERVALS_AMOUNT, 0))
                )
            )
        )->count(self::TASKS_AMOUNT);
    }

    private function createUser($email, $roleId, $projectRoleId = null): void
    {
        User::factory()->afterCreating(
            function (User $user) use ($projectRoleId) {
                $project = Project::factory()->has(
                    $this->createTasks($user)
                )->count(self::PROJECTS_AMOUNT);

                if ($projectRoleId) {
                    $project = $project->afterCreating(
                        function (Project $project) use ($user, $projectRoleId) {
                            $this->projectMemberService->syncMembers(
                                $project->id,
                                [$user->id => ['role_id' => $projectRoleId]]
                            );
                        }
                    );
                }

                $project->create();
            }
        )->create(['email' => $email, 'role_id' => $roleId]);
    }

    public function run(): void
    {
        $this->createUser('projectManager@example.com', 2, 1);
        $this->createUser('projectAuditor@example.com', 2, 3);

        $this->createUser('auditor@example.com', 3);
        $this->createUser('manager@example.com', 1);
        $this->createUser('user@example.com', 2);
    }
}
