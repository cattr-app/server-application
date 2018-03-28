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

        foreach (User::all() as $user) {
            $this->seedProjects($user);
        }
    }

    protected function seedProjects(User $user)
    {
        $faker = $this->faker;

        for ($i = 0; $i < 5; $i++) {
            $project = Project::create([
                'company_id' => $i,
                'name' => $faker->text(random_int(10, 50)),
                'description' => $faker->text(random_int(100, 1000)),
            ]);

            $this->seedTasks($project, $user);
        }
    }

    protected function seedTasks(Project $project, User $user)
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

            $this->seedTimeIntervals($task, $user);
        }
    }

    protected function seedTimeIntervals(Task $task, User $user)
    {
        static $time = [];

        if (!isset($time[$user->id])) {
            $time[$user->id] = time() - 388800000;
        }

        $faker = $this->faker;

        $time[$user->id] += 3600 * 10;

        for ($i = 0; $i < 30; $i++) {
            $start = $time[$user->id] + 1;
            $time[$user->id] += (5 * 60) - 1;
            $end = $time[$user->id];

            $interval = TimeInterval::create([
                'task_id' => $task->id,
                'start_at' => date('Y-m-d H:i:s', $start),
                'end_at' => date('Y-m-d H:i:s', $end),
            ]);

            $this->seedScreenshot($interval, $user);
        }
    }

    protected function seedScreenshot(TimeInterval $interval, User $user)
    {
        //factory(Screenshot::class, 1)->create();
    }
}
