<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Screenshot;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Storage;

/**
 * Class TaskListSeeder
 */
class TaskListSeeder extends Seeder
{

    protected Generator $faker;

    protected const PROTECTED_FILES = ['uploads/screenshots/.gitignore'];

    /**
     * Run the database seeds.
     * @throws Exception
     */
    public function run(): void
    {
        $this->faker = Factory::create();

        $this->command->getOutput()->writeln('<fg=yellow>Create dummy data</>');

        foreach (User::all() as $user) {
            $this->seedProjects($user);
        }

        $this->command->getOutput()->writeln('<fg=green>Dummy data has been seeded</>');
    }

    /**
     * @param User $user
     * @throws Exception
     */
    protected function seedProjects(User $user): void
    {
        $this->command->getOutput()->writeln("<fg=yellow>- Seed data for user #{$user->id}</>");

        foreach (range(0, 4) as $i) {
            $project = Project::create([
                'company_id' => $i,
                'name' => $this->faker->text($i * 10 + 5),
                'description' => $this->faker->text($i * 100 + 5),
            ]);

            $this->command->getOutput()->writeln("<fg=cyan>- Project #{$project->id}</>");

            $this->seedTasks($project, $user);
        }
    }

    /**
     * @param Project $project
     * @param User $user
     * @throws Exception
     */
    protected function seedTasks(Project $project, User $user): void
    {
        foreach (range(0, 20) as $i) {
            $task = Task::create([
                'project_id' => $project->id,
                'task_name' => $this->faker->text(15 + $i),
                'description' => $this->faker->text(100 + $i * 15),
                'active' => true,
                'assigned_by' => $user->id,
                'priority_id' => 2, // Normal
            ]);

            $task->users()->sync([$user->id]);

            $this->command->getOutput()->writeln("<fg=cyan>-- {$project->id}. Task #{$task->id}</>");

            $this->seedTimeIntervals($task, $user);

        }
    }

    /**
     * @param Task $task
     * @param User $user
     */
    protected function seedTimeIntervals(Task $task, User $user): void
    {
        static $time = [];

        if (!isset($time[$user->id])) {
            $time[$user->id] = time();
        }

        foreach (range(0, 4) as $i) {

            $this->command->getOutput()->writeln("<fg=cyan>--- {$task->project->id}.{$task->id}. Interval #{$i}</>");

            $intervalsOffset = random_int(0, 60 * 20);

            $end = $time[$user->id] - $intervalsOffset;
            $time[$user->id] -= $intervalsOffset + random_int(60 * 30, 60 * 50);
            $start = $time[$user->id];

            $mouseFill = mt_rand(0, 100);
            $keyboardFill = mt_rand(0, 100 - $mouseFill);
            $activityFill = $mouseFill + $keyboardFill;

            $interval = TimeInterval::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'start_at' => date('Y-m-d H:i:s', $start),
                'end_at' => date('Y-m-d H:i:s', $end),
                'activity_fill' => $activityFill,
                'mouse_fill' => $mouseFill,
                'keyboard_fill' => $keyboardFill,
            ]);

            $this->seedScreenshot($interval);
        }

        $time[$user->id] -= random_int(0, 60 * 60 * 5);
    }

    protected function seedScreenshot(TimeInterval $interval): void
    {
        $screenshots = array_diff(Storage::files('uploads/screenshots'), self::PROTECTED_FILES);
        $path = $screenshots[array_rand($screenshots)];
        $thumbnail = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $path);

        Screenshot::create([
            'time_interval_id' => $interval->id,
            'path' => $path,
            'thumbnail_path' => $thumbnail
        ]);
    }
}
