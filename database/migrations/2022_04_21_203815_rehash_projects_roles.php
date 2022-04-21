<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('tasks')
            ->lazyById()
            ->each(static function ($task) {
                DB::table('tasks_users')
                    ->join('users', 'tasks_users.user_id', '=', 'users.id')
                    ->where('tasks_users.task_id', '=', $task->id)
                    ->lazyById()
                    ->each(static function ($user) use ($task) {
                        if (DB::table('projects_users')
                                ->where('project_id', '=', $task->project_id)
                                ->where('user_id', '=', $user->id)
                                ->doesntExist()) {
                            DB::table('projects_users')->insert([
                                'user_id' => $user->id,
                                'project_id' => $task->project_id,
                                'role_id' => 2,
                            ]);
                        }
                    });
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // No reverse... sorry
    }
};
