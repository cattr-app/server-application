<?php

use App\Models\Project;
use App\Models\ProjectsUsers;
use App\Models\Role;
use App\Models\Screenshot;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

/**
 * Creates additional users to statistics tests.
 */
class StatisticsSeeder extends Seeder
{
    protected array $protectedFiles = ['uploads/screenshots/.gitignore'];

    /**
     * Creates a new user.
     * @param string $name
     * @param string $email
     * @param string $pass
     * @param int $roleId
     * @return User
     */
    protected function createUser(string $name, string $email, string $pass, int $roleId): User
    {
        $user = User::updateOrCreate([
            'full_name' => $name,
            'email' => $email,
            'url' => '',
            'company_id' => 1,
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
            'active' => true,
            'password' => $pass,
            'role_id' => $roleId,
        ]);

        $this->command->getOutput()->writeln("<fg=green>$name user has been created</>");

        return $user;
    }

    /**
     * Assignees user to a project.
     * @param User $user
     * @param Project $project
     * @param int|null $roleId
     * @return ProjectsUsers
     */
    protected function assignProject(User $user, Project $project, ?int $roleId = null): ProjectsUsers
    {
        $userProjectRole = [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ];

        if (isset($roleId)) {
            $userProjectRole['role_id'] = $roleId;
        }

        $relation = ProjectsUsers::create($userProjectRole);

        $this->command->getOutput()->writeln("<fg=green>{$user->full_name} assigned to project {$project->id}</>");

        return $relation;
    }

    /**
     * Adds task for an user on a project.
     * @param User $user
     * @param Project $project
     * @return Task
     */
    protected function addTask(User $user, Project $project): Task
    {
        $faker = FakerFactory::create();

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
     * @param User $user
     * @param Task $task
     * @return TimeInterval
     */
    protected function addTimeInterval(User $user, Task $task): TimeInterval
    {
        static $time = [];

        if (!isset($time[$user->id])) {
            $time[$user->id] = time();
        }

        $intervalsOffset = random_int(0, 60 * 60 * 5);

        $end = $time[$user->id] - $intervalsOffset;
        $time[$user->id] -= random_int($intervalsOffset, 60 * 60 * 5);
        $start = $time[$user->id];

        $interval = TimeInterval::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'start_at' => date('Y-m-d H:i:s', $start),
            'end_at' => date('Y-m-d H:i:s', $end),
            'mouse_fill' => random_int(0, 150),
            'keyboard_fill' => random_int(0, 150),
            'activity_fill' => random_int(0, 150),
        ]);

        $this->command->getOutput()->writeln("<fg=cyan>Added time interval for task {$task->id} for user {$user->id}</>");

        $this->seedScreenshot($interval);

        return $interval;
    }

    protected function seedScreenshot(TimeInterval $interval): void
    {
        $screenshots = array_diff(Storage::files('uploads/screenshots'), $this->protectedFiles);

        $path = $screenshots[array_rand($screenshots)];
        $newName = uniqid('', true);
        $newPath = "uploads/screenshots/$newName.jpg";
        Storage::copy($path, $newPath);

        $thumbnail = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $path);
        $newThumbnail = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $newPath);

        Storage::copy($thumbnail, $newThumbnail);

        Screenshot::create([
            'time_interval_id' => $interval->id,
            'path' => $newPath,
            'thumbnail_path' => $newThumbnail
        ]);
    }


    /**
     * Creates tasks and time intervals for an user on a project.
     * @param User $user
     * @param Project $project
     */
    protected function createTasks(User $user, Project $project): void
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
     * @throws Exception
     */
    public function run(): void
    {
        $projects = Project::all();

        $userRoleId = Role::where('name', 'user')->first()->id;
        $managerRoleId = Role::where('name', 'manager')->first()->id;
        $auditorRoleId = Role::where('name', 'auditor')->first()->id;

        $manager = $this->createUser('Manager', 'manager@example.com', 'password', $managerRoleId);
        $this->assignProject($manager, $projects[0]);
        $this->assignProject($manager, $projects[1]);
        $this->assignProject($manager, $projects[2]);

        $userAuditor = $this->createUser('Auditor', 'auditor@example.com', 'password', $auditorRoleId);
        $this->assignProject($userAuditor, $projects[0]);
        $this->createTasks($userAuditor, $projects[0]);

        $user = $this->createUser('User', 'user@example.com', 'password', $userRoleId);
        $this->assignProject($user, $projects[0]);
        $this->assignProject($user, $projects[1]);
        $this->createTasks($user, $projects[0]);
        $this->createTasks($user, $projects[1]);

        $user2 = $this->createUser(
            "User (Project {$projects[2]['name']} manager)",
            'user2@example.com',
            'password',
            $userRoleId
        );
        $this->assignProject($user2, $projects[2], 1);
        $this->assignProject($user2, $projects[3]);
        $this->createTasks($user2, $projects[2]);

        $user3 = $this->createUser(
            "User (Project {$projects[2]['name']} auditor)",
            'user3@example.com',
            'password',
            $userRoleId
        );
        $this->assignProject($user3, $projects[2]);
        $this->assignProject($user3, $projects[3], 3);
        $this->createTasks($user3, $projects[2]);
        $this->createTasks($user3, $projects[3]);
    }
}
