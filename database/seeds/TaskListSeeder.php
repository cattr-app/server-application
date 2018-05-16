<?php

use Illuminate\Database\Seeder;

use App\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\Screenshot;

use Faker\Factory;
use Faker\Generator;

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

        for ($i = 0; $i < 5; $i++) {
            $project = Project::create([
                'company_id' => $i,
                'name' => $faker->text(random_int(10, 50)),
                'description' => $faker->text(random_int(100, 1000)),
            ]);

            $this->command->getOutput()->writeln("<fg=cyan>- Project #{$project->id}</>");

            $this->seedTasks($project, $user);
        }
    }

    protected function seedTasks(Project $project, User $user): void
    {
        $faker = $this->faker;


        for ($i = 0; $i < 15; $i++) {
            $task = Task::create([
                'project_id' => $project->id,
                'task_name' => $faker->text(random_int(15, 50)),
                'description' => $faker->text(random_int(100, 1000)),
                'active' => true,
                'user_id' => $user->id,
                'assigned_by' => $user->id,
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

        for ($i = 0; $i < 30; $i++) {

            $this->command->getOutput()->writeln("<fg=cyan>--- {$task->project->id}.{$task->id}. Interval #{$i}</>");

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

            $this->seedScreenshot($interval, $user);
        }
    }

    protected function seedScreenshot(TimeInterval $interval, User $user): void
    {
        $placeholderLink = "http://via.placeholder.com/1600x900/{$this->random_color()}/{$this->random_color()}.png?" . http_build_query([
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

        Screenshot::create([
            'time_interval_id' => $interval->id,
            'path' => $filePath,
        ]);
    }

    private function random_color_part(): string
    {
        return str_pad( dechex( random_int( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }

    private function random_color(): string
    {
        return $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
    }
}
