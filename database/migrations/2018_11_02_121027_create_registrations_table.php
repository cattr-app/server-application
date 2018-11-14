<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('registrations')) {
            Schema::create('registrations', function (Blueprint $table) {
                $table->increments('id');
                $table->uuid('key')->unique();
                $table->string('email');
                $table->dateTime('expires_at');
            });
        }

        // Allow root to register users.
        $root_role_id = 1;
        $blocked_role_id = 255;
        $role_ids = Role::where('id', '<>', $blocked_role_id)
            ->pluck('id')
            ->toArray();
        if (in_array($root_role_id, $role_ids)) {
            Rule::updateOrCreate([
                'role_id' => $root_role_id,
                'object' => 'register',
                'action' => 'create',
            ], [
                'allow' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registrations');
    }
}
