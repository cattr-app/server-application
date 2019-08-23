<?php

use App\Models\Project;
use App\Models\Screenshot;
use App\Models\Task;
use App\Models\TimeInterval;
use App\User;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;

class TaskListSeeder extends Seeder
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * Run the database seeds.
     *
     * @return void
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

    protected function seedProjects(User $user): void
    {
        $faker = $this->faker;

        $this->command->getOutput()->writeln("<fg=yellow>- Seed data for user #{$user->id}</>");

        foreach (range(0, 4) as $i) {
            $project = Project::create([
                'company_id' => $i,
                'name' => $faker->text($i * 10 + 5),
                'description' => $faker->text($i * 100 + 5),
            ]);

            $this->command->getOutput()->writeln("<fg=cyan>- Project #{$project->id}</>");

            $this->seedTasks($project, $user);
        }
    }

    protected function seedTasks(Project $project, User $user): void
    {
        $faker = $this->faker;

        foreach (range(0, 14) as $i) {
            $task = Task::create([
                'project_id' => $project->id,
                'task_name' => $faker->text(15 + $i),
                'description' => $faker->text(100 + $i * 15),
                'active' => true,
                'user_id' => $user->id,
                'assigned_by' => $user->id,
                'priority_id' => 2, // Normal
            ]);

            $this->command->getOutput()->writeln("<fg=cyan>-- {$project->id}. Task #{$task->id}</>");

            $this->seedTimeIntervals($task, $user);
        }
    }

    protected function seedTimeIntervals(Task $task, User $user): void
    {
        static $time = [];

        if (!isset($time[$user->id])) {
            $time[$user->id] = time() - 388800000;
        }

        $time[$user->id] += 3600 * 10;

        foreach (range(0, 4) as $i) {

            $this->command->getOutput()->writeln("<fg=cyan>--- {$task->project->id}.{$task->id}. Interval #{$i}</>");

            $start = $time[$user->id] + 1;
            $time[$user->id] += (5 * 60) - 1;
            $end = $time[$user->id];

            $interval = TimeInterval::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'start_at' => date('Y-m-d H:i:s', $start),
                'end_at' => date('Y-m-d H:i:s', $end),
                'count_mouse' => 42,
                'count_keyboard' => 43
            ]);

            $this->seedScreenshot($interval, $user);
        }
    }

    /** @var bool */
    protected $_isScreenshotDownloaded = false;

    protected $_filePath;

    protected function seedScreenshot(TimeInterval $interval, User $user): void
    {
        if ($this->_isScreenshotDownloaded === false) {
            $placeholderLink = "http://via.placeholder.com/1600x900/{$this->random_color()}/{$this->random_color()}.png?"
                .http_build_query([
                    'text' => "#{$interval->id} - {$interval->task->task_name}",
                ]);

            $filePath = "uploads/screenshots/{$user->id}_{$interval->task_id}_{$interval->id}.png";

            /** @var Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('local');

            if (!$disk->exists($filePath)) {
                $this->command->getOutput()->writeln('<fg=cyan>---- Generate Screenshot</>');

                $fileData = file_get_contents($placeholderLink);
                $disk->put($filePath, $fileData);
            }

            $this->_filePath = $filePath;
        }

        Screenshot::create([
            'time_interval_id' => $interval->id,
            'path' => $this->_filePath,
        ]);
    }

    protected function random_color_part(): string
    {
        return str_pad(dechex(random_int(0, 255)), 2, '0', STR_PAD_LEFT);
    }

    protected function random_color(): string
    {
        return $this->random_color_part().$this->random_color_part().$this->random_color_part();
    }
}
