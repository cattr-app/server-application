<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Role;
use App\Models\Rule;
use App\User;


class CreateRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Role::create(['id' => '1', 'name' => 'user']);
        Role::create(['id' => '2', 'name' => 'root']);
        Role::create(['id' => '3', 'name' => 'blocked']);


        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->default('2');

            $table->foreign('role_id')->references('id')->on('role');
        });




        Schema::create('rule', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_id');
            $table->string('object');
            $table->string('action');
            $table->boolean('allow')->default(false);
            $table->timestamps();


            $table->unique(['role_id', 'object', 'action']);
            $table->foreign('role_id')->references('id')->on('role')->onDelete('cascade');
        });



        foreach (Rule::getActionList() as $object => $actions) { // allow everything to root
            foreach ($actions as $action => $action_name) {
                Rule::create(['role_id' => '2', 'object' => $object, 'action' => $action, 'allow' => true]);
            }
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_role_id_foreign');
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('rule');

        Schema::dropIfExists('role');

    }
}
