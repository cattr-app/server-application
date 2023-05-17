<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('screenshots_active')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('screenshots_active', 'enable_screenshots');
        });

        User::where('enable_screenshots', '1')->where('enable_screenshots', null)->update(['enable_screenshots' => 'required']);
        User::where('enable_screenshots', '0')->update(['enable_screenshots' => 'forbidden']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('enable_screenshots', 'required')->update(['enable_screenshots' => '1']);
        User::where('enable_screenshots', 'forbidden')->update(['enable_screenshots' => '0']);

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('screenshots_active')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('enable_screenshots', 'screenshots_active');
        });
    }
};
