<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProjectsEmailReportRelation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('email_reports', 'project_ids')) {
            Schema::table('email_reports', function (Blueprint $table) {
                $table->dropColumn('project_ids');
                $table->text('document_type');
                $table->dropColumn('value');
                $table->dateTime('sending_day');
            });
        }

        Schema::create('email_reports_projects', function (Blueprint $table) {
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
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_reports_projects');
        if (!Schema::table('email_reports')->hasColumn('project_ids')) {
            Schema::table('email_reports', function (Blueprint $table) {
                $table->text('project_ids')->nullable();
                $table->dropColumn('document_type')->nullable();
            });
        }
    }
}
