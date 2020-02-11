<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entity_id');
            $table->string('entity_type', 15);
            $table->string('name', 150);
            $table->text('value');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['entity_id', 'entity_type', 'name']);
            $table->index(['entity_id', 'entity_type'], 'properties_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_index');
        });

        Schema::dropIfExists('properties');
    }
}
