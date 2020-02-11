<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIssueIidColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gitlab_tasks_relations', function (Blueprint $table) {
            $table->unsignedBigInteger('gitlab_issue_iid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gitlab_tasks_relations', function (Blueprint $table) {
            $table->dropColumn('gitlab_issue_iid');
        });
    }
}
