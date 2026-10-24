<?php

namespace Tests\Unit;

use App\Jobs\ClearExpiredApps;
use App\Models\Invitation;
use App\Models\Priority;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TimeInterval;
use App\Models\User;
use App\Policies\InvitationPolicy;
use App\Policies\PriorityPolicy;
use App\Policies\ProjectGroupPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\StatusPolicy;
use App\Policies\TaskCommentPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TimeIntervalPolicy;
use App\Policies\UserPolicy;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Structural checks for things the Laravel 11+ skeleton discovers instead of registering explicitly.
 */
class ApplicationBootstrapTest extends TestCase
{
    public static function policyProvider(): array
    {
        return [
            'ProjectGroup' => [ProjectGroup::class, ProjectGroupPolicy::class],
            'Project' => [Project::class, ProjectPolicy::class],
            'Task' => [Task::class, TaskPolicy::class],
            'User' => [User::class, UserPolicy::class],
            'TimeInterval' => [TimeInterval::class, TimeIntervalPolicy::class],
            'Priority' => [Priority::class, PriorityPolicy::class],
            'Status' => [Status::class, StatusPolicy::class],
            'Invitation' => [Invitation::class, InvitationPolicy::class],
            'TaskComment' => [TaskComment::class, TaskCommentPolicy::class],
        ];
    }

    #[DataProvider('policyProvider')]
    public function test_policy_is_discovered_for_model(string $model, string $policy): void
    {
        $this->assertInstanceOf($policy, Gate::getPolicyFor($model));
    }

    public function test_schedule_contains_all_entries(): void
    {
        $events = collect(app(Schedule::class)->events());

        // Installed modules may add their own entries; only the core ones are asserted here.
        $this->assertGreaterThanOrEqual(6, $events->count());

        $expected = [
            ['cattr:screenshots:rotate', '0 0 * * 0'],
            [ClearExpiredApps::class, '0 0 * * *'],
            ['cattr:task:recreate-workers', '0 0 * * *'],
            ['cattr:attachments:verify', '0 0 * * *'],
            ['cattr:calculate-efficiency', '0 0 * * *'],
            ['cattr:attachments:find-sus-files', '0 0 * * 0'],
        ];

        foreach ($expected as [$needle, $expression]) {
            $matches = $events->filter(
                static fn (Event $event) => str_contains((string) ($event->command ?? $event->description), $needle)
                    && $event->expression === $expression
            );

            $this->assertCount(1, $matches, "Scheduled entry $needle ($expression) is missing");
        }
    }
}
