<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Module activation is controlled by MODULE_BACKEND_<FLAG_NAME>_ENABLED environment
 * variables; the legacy database-backed module statuses are no longer read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('modules');
    }

    public function down(): void
    {
        // Module statuses are environment-controlled; the legacy table is not recreated.
    }
};
