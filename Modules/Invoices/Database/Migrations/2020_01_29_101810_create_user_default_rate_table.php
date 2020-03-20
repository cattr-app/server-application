<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDefaultRateTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_default_rate', static function (Blueprint $table) {
            Schema::disableForeignKeyConstraints();
            $table->unsignedInteger('user_id');
            $table->float('default_rate', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_default_rate');
    }
}
