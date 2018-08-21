<?php

use App\Models\Project;
use App\Models\ProjectsUsers;
use App\Models\RelationsUsers;
use App\Models\Rule;
use App\Models\Task;
use App\Models\TimeInterval;
use App\User;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Creates additional users to statistics tests.
 */
class StatisticsSeeder extends Seeder
{
    /**
     * Creates a new user.
     */
    protected function createUser(string $name, string $email, string $pass, int $role_id) : User
    {
        $user = User::updateOrCreate([
            'full_name' => $name,
            'first_name' => $name,
            'last_name' => '',
            'email' => $email,
            'url' => '',
            'company_id' => 1,
            'level' => '',
            'role_id' => $role_id,
            'payroll_access' => 1,
            'billing_access' => 1,
            'avatar' => '',
            'screenshots_active' => 1,
            'manual_time' => 0,
            'permanent_tasks' => 0,
            'computer_time_popup' => 300,
            'poor_time_popup' => '',
            'blur_screenshots' => 0,
            'web_and_app_monitoring' => 1,
            'webcam_shots' => 0,
            'screenshots_interval' => 9,
            'user_role_value' => '',
            'active' => 'active',
            'password' => bcrypt($pass),
        ]);

        $this->command->getOutput()->writeln("<fg=green>$name user has been created</>");

        return $user;
    }

    /**
     * Assignes user to a project.
     */
    protected function assignProject(User $user, Project $project) : ProjectsUsers
    {
        $relation = ProjectsUsers::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $this->command->getOutput()->writeln("<fg=green>{$user->full_name} assigned to project {$project->id}</>");

        return $relation;
    }

    /**
     * Adds task for an user on a project.
     */
    protected function addTask(User $user, Project $project) : Task
    {
        $faker = $this->faker;

        $task = $task = Task::create([
            'project_id' => $project->id,
            'task_name' => $faker->text(random_int(15, 50)),
            'description' => $faker->text(random_int(100, 1000)),
            'active' => true,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
        ]);

        $this->command->getOutput()->writeln("<fg=cyan>Added task {$task->id} to project {$project->id}</>");

        return $task;
    }

    /**
     * Adds time interval for an user on a project.
     */
    protected function addTimeInterval(User $user, Task $task) : TimeInterval
    {
        static $time = [];

        if (!isset($time[$user->id])) {
            $time[$user->id] = time() - 388800000;
        }

        $time[$user->id] += 3600 * 10;

        $start = $time[$user->id] + 1;
        $time[$user->id] += (5 * 60) - 1;
        $end = $time[$user->id];

        $interval = TimeInterval::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'start_at' => date('Y-m-d H:i:s', $start),
            'end_at' => date('Y-m-d H:i:s', $end),
            'count_mouse' => random_int(0, 150),
            'count_keyboard' => random_int(0, 150)
        ]);

        $this->command->getOutput()->writeln("<fg=cyan>Added time interval for task {$task->id} for user {$user->id}</>");

        return $interval;
    }

    /**
     * Attaches user to another user.
     */
    protected function attachUser(User $user, User $attached) : RelationsUsers
    {
        $relation = RelationsUsers::create([
            'attached_user_id' => $attached->id,
            'user_id' => $user->id,
        ]);

        $this->command->getOutput()->writeln("<fg=green>{$attached->full_name} attached to user {$user->full_name}</>");

        return $relation;
    }

    /**
     * Creates tasks and time intervals for an user on a project.
     */
    protected function createTasks(User $user, Project $project) : void
    {
        for ($i = 0; $i < 5; ++$i) {
            $task = $this->addTask($user, $project);
            for ($j = 0; $j < 10; ++$j) {
                $this->addTimeInterval($user, $task);
            }
        }
    }

    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $this->faker = Factory::create();

        $projects = Project::all();

        $manager = $this->createUser('Manager', 'manager@example.com', 'manager', 5);
        $this->assignProject($manager, $projects[0]);
        $this->assignProject($manager, $projects[1]);
        $this->assignProject($manager, $projects[2]);

        $user1 = $this->createUser('User 1', 'user1@example.com', 'user1', 2);
        $this->assignProject($user1, $projects[0]);
        $this->createTasks($user1, $projects[0]);
        $this->attachUser($manager, $user1);

        $user2 = $this->createUser('User 2', 'user2@example.com', 'user2', 2);
        $this->assignProject($user2, $projects[0]);
        $this->assignProject($user2, $projects[1]);
        $this->createTasks($user2, $projects[0]);
        $this->createTasks($user2, $projects[1]);
        $this->attachUser($manager, $user2);

        $user3 = $this->createUser('User 3', 'user3@example.com', 'user3', 2);
        $this->assignProject($user3, $projects[2]);
        $this->assignProject($user3, $projects[3]);
        $this->createTasks($user3, $projects[2]);
        $this->createTasks($user3, $projects[3]);
        $this->attachUser($manager, $user3);

        $user4 = $this->createUser('User 4', 'user4@example.com', 'user4', 2);
        $this->assignProject($user4, $projects[3]);
        $this->createTasks($user4, $projects[3]);
        $this->attachUser($manager, $user4);

        $user5 = $this->createUser('User 5', 'user5@example.com', 'user5', 2);
        $this->assignProject($user5, $projects[4]);
        $this->createTasks($user5, $projects[4]);
    }
}
