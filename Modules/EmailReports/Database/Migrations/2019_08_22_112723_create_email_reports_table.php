<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_reports', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 255);
            $table->string('email', 255);
            $table->json('project_ids')->nullable();
            $table->tinyInteger('frequency');
            $table->tinyInteger('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_reports');
    }
}
