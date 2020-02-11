<?php

use App\Models\Project;
use App\Models\ProjectsUsers;
use App\Models\RelationsUsers;
use App\Models\Role;
use App\Models\Screenshot;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;

/**
 * Creates additional users to statistics tests.
 */
class StatisticsSeeder extends Seeder
{
    /** @var bool */
    protected $_isScreenshotDownloaded = false;

    protected $_filePath;

    protected $_fileData;

    /**
     * Creates a new user.
     */
    protected function createUser(string $name, string $email, string $pass, int $role_id): User
    {
        /** @var User $user */
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
            'password' => bcrypt($pass),
            'role_id' => $role_id,
        ]);

        $this->command->getOutput()->writeln("<fg=green>$name user has been created</>");

        return $user;
    }

    /**
     * Assignes user to a project.
     */
    protected function assignProject(User $user, Project $project, int $role_id = null): ProjectsUsers
    {
        $userProjectRole = [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ];

        if(isset($role_id)) {
            $userProjectRole['role_id'] = $role_id;
        }

        $relation = ProjectsUsers::create($userProjectRole);

        $this->command->getOutput()->writeln("<fg=green>{$user->full_name} assigned to project {$project->id}</>");

        return $relation;
    }

    /**
     * Adds task for an user on a project.
     */
    protected function addTask(User $user, Project $project): Task
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
    protected function addTimeInterval(User $user, Task $task): TimeInterval
    {
        static $time = [];

        if (!isset($time[$user->id])) {
            $time[$user->id] = gmmktime(0, 0, 0);;
        }

        $offsetBetweenIntervals = rand(0, 60 * 60 * 5);

        $start = $time[$user->id] + $offsetBetweenIntervals;
        $time[$user->id] += rand($offsetBetweenIntervals, 60 * 60 * 5);
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

        $this->seedScreenshot($interval, $user);

        return $interval;
    }

    /**
     * Adds screenshot for time interval
     *
     * @param TimeInterval $interval
     * @param User $user
     */
    protected function seedScreenshot(TimeInterval $interval, User $user): void
    {
        if ($this->_isScreenshotDownloaded === false) {
            $placeholderLink = "https://via.placeholder.com/1600x900/{$this->random_color()}/{$this->random_color()}.png?"
                .http_build_query([
                    'text' => "#{$interval->id} - {$interval->task->task_name}",
                ]);

            $this->_isScreenshotDownloaded = true;
            $this->_fileData = file_get_contents($placeholderLink);
        }

        $filePath = "uploads/screenshots/{$user->id}_{$interval->task_id}_{$interval->id}.png";

        /** @var Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if (!$disk->exists($filePath)) {
            $this->command->getOutput()->writeln('<fg=cyan>---- Generate Screenshot</>');


            $disk->put($filePath, $this->_fileData);
        }

        $this->_filePath = $filePath;


        Screenshot::create([
            'time_interval_id' => $interval->id,
            'path' => $this->_filePath,
        ]);
    }

    /**
     * Creates tasks and time intervals for an user on a project.
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

    protected function random_color_part(): string
    {
        return str_pad(dechex(random_int(0, 255)), 2, '0', STR_PAD_LEFT);
    }

    protected function random_color(): string
    {
        return $this->random_color_part().$this->random_color_part().$this->random_color_part();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->faker = Factory::create();

        $projects = Project::all();

        $userRoleId = Role::where('name', 'user')->first()->getAttribute('id');
        $managerRoleId = Role::where('name', 'manager')->first()->getAttribute('id');
        $auditorRoleId = Role::where('name', 'auditor')->first()->getAttribute('id');

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

        $user2 = $this->createUser("User (Project {$projects[2]['name']} manager)", 'user2@example.com', 'password', $userRoleId);
        $this->assignProject($user2, $projects[2], 1);
        $this->assignProject($user2, $projects[3]);
        $this->createTasks($user2, $projects[2]);

        $user3 = $this->createUser("User (Project {$projects[2]['name']} auditor)", 'user3@example.com', 'password', $userRoleId);
        $this->assignProject($user3, $projects[2]);
        $this->assignProject($user3, $projects[3], 3);
        $this->createTasks($user3, $projects[2]);
        $this->createTasks($user3, $projects[3]);
    }
}
