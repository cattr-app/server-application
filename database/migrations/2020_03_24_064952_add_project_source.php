<?php

use App\Models\Property;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectSource extends Migration
{
    protected function updateProjectSource()
    {
        $projectIds = DB::table(Property::getTableName())
            ->select('entity_id')
            ->where('entity_type', '=', Property::PROJECT_CODE)
            ->where('name', '=', 'REDMINE_ID')
            ->pluck('entity_id');

        DB::table('projects')
            ->whereIn('id', $projectIds)
            ->update(['source' => 'redmine']);

        if (Schema::hasTable('gitlab_projects_relations')) {
            $projectIds = DB::table('gitlab_projects_relations')
                ->select('project_id')
                ->pluck('project_id');

            DB::table('projects')
                ->whereIn('id', $projectIds)
                ->update(['source' => 'gitlab']);
        }

        if (Schema::hasTable('jira_projects_relation')) {
            $projectIds = DB::table('jira_projects_relation')
                ->select('project_id')
                ->pluck('project_id');

            DB::table('projects')
                ->whereIn('id', $projectIds)
                ->update(['source' => 'jira']);
        }

        if (Schema::hasTable('trello_projects_relation')) {
            $projectIds = DB::table('trello_projects_relation')
                ->select('project_id')
                ->pluck('project_id');

            DB::table('projects')
                ->whereIn('id', $projectIds)
                ->update(['source' => 'trello']);
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('source')->default('internal');
        });

        $this->updateProjectSource();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
}
