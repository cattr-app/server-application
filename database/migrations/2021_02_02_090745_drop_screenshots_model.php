<?php

use App\Contracts\ScreenshotService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('screenshots')
            ->lazyById()
            ->each(static function ($screenshot) {
                rescue(static fn() => app(ScreenshotService::class)->saveScreenshot(
                    Storage::path($screenshot->path),
                    $screenshot->time_interval_id
                ));
            });

        Storage::deleteDirectory('uploads/screenshots');

        Schema::drop('screenshots');
    }

    public function down(): void
    {
        //
    }
};
