<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectsEmailReportRelation extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('email_reports', 'project_ids')) {
            Schema::table('email_reports', static function (Blueprint $table) {
                $table->dropColumn('project_ids');
                $table->text('document_type');
                $table->dropColumn('value');
                $table->dateTime('sending_day');
            });
        }

        Schema::create('email_reports_projects', static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('email_projects_id');
            $table->integer('project_id')->unsigned();
            $table->timestamps();

            $table->foreign('email_projects_id')->references('id')->on('email_reports')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_reports_projects');
        if (!Schema::hasColumn('email_reports', 'project_ids')) {
            Schema::table('email_reports', static function (Blueprint $table) {
                $table->text('project_ids')->nullable();
                $table->dropColumn('document_type')->nullable();
            });
        }
    }
}
