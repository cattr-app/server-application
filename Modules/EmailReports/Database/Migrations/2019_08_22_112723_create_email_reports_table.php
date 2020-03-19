<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailReportsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_reports', static function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 255);
            $table->string('email', 255);
            $table->text('project_ids')->nullable();
            $table->tinyInteger('frequency');
            $table->tinyInteger('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_reports');
    }
}
