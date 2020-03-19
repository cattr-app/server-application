<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJiraProjectConstraints extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jira_projects_relation', static function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jira_projects_relation', static function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
    }
}
