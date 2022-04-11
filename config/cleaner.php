<?php

use App\Contracts\ScreenshotService;

return [
    'total_space' => env(
        'SCREENSHOTS_STORAGE_SIZE_BYTES',
        disk_total_space(ScreenshotService::getFullPath())
    ),
    'threshold' => env('CLEANER_THRESHOLD_USED_SPACE_PERCENTAGE', 75),
    'waterline' => env('CLEANER_WATERLINE_PERCENTAGE', 15),
    'page_size' => 10,
];
