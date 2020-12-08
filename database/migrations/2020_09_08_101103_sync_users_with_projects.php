<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use App\Models\Project;

class SyncUsersWithProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')
            ->leftJoin('time_intervals', 'users.id', '=', 'time_intervals.user_id')
            ->leftJoin('tasks', 'tasks.id', '=', 'time_intervals.task_id')
            ->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->selectRaw('users.id as user_id, projects.id as project_id')
            ->distinct('users.id', 'project_id')
            ->get()
            ->map(function ($userProject) {
                User::withoutGlobalScopes()
                    ->find($userProject->user_id)
                    ->projects()
                    ->syncWithoutDetaching($userProject->project_id);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Project::all()->map(function ($project) {
            /* @var App\Models\Project $project*/
            $project->users()->wherePivot('role_id', 2)->detach();
        });
    }
}
