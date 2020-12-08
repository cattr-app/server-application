<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('rule');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('rule', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_id');
            $table->string('object', 50);
            $table->string('action', 50);
            $table->boolean('allow')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'object', 'action']);
            $table->foreign('role_id')->references('id')->on('role')->onDelete('cascade');
        });
    }
}
