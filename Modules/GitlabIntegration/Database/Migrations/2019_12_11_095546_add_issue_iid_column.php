<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIssueIidColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gitlab_tasks_relations', static function (Blueprint $table) {
            $table->unsignedBigInteger('gitlab_issue_iid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gitlab_tasks_relations', static function (Blueprint $table) {
            $table->dropColumn('gitlab_issue_iid');
        });
    }
}
