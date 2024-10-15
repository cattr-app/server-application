<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('project_groups', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            /**
             * From laravel-nestedset package
             */
            $table->nestedSet();
        });

        Schema::table('projects', static function (Blueprint $table) {
            $table->foreignId('group')->nullable()->constrained('project_groups')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('projects', static function (Blueprint $table) {
            $table->dropForeign('projects_group_foreign');
            $table->dropColumn('group');
        });

        Schema::dropIfExists('project_groups');
    }
};
