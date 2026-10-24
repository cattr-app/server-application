<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel Telescope was removed from the project; drop its tables from
 * installations where they were created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_entries');
        Schema::dropIfExists('telescope_monitoring');
    }

    public function down(): void
    {
        // Telescope is no longer part of the project; its schema is not recreated.
    }
};
