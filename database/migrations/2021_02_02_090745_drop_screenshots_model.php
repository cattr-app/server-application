<?php

use App\Contracts\ScreenshotService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('screenshots')
            ->lazyById()
            ->each(static function ($screenshot) {
                app(ScreenshotService::class)->saveScreenshot(
                    Storage::path($screenshot->path),
                    $screenshot->time_interval_id
                );
            });

        Storage::deleteDirectory('uploads/screenshots');

        Schema::drop('screenshots');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
};
