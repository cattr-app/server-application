<?php

use Database\Seeders\StatusSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active');
            $table->timestamps();
        });

        $seeder = new StatusSeeder();
        $seeder->run();

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('status_id')->nullable();
        });

        DB::table('tasks')
            ->where('active', 1)
            ->update(['status_id' => 1]);

        DB::table('tasks')
            ->where('active', 0)
            ->update(['status_id' => 2]);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('active');
        });

        DB::table('tasks as t')
            ->join('statuses as s', 't.status_id', '=', 's.id')
            ->update(['t.active' => DB::raw('s.active')]);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });

        Schema::dropIfExists('statuses');
    }
}
