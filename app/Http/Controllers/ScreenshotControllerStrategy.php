<?php

namespace App\Http\Controllers;

use App\Models\Screenshot;
use Illuminate\Http\Request;

class ScreenshotControllerStrategy implements ScreenshotControllerStrategyInterface
{
    public function getScreenshot(Request $request): ?Screenshot
    {
        $path = $request->path();

        return Screenshot::with('timeInterval')
            ->where('path', $path)
            ->first();
    }

    public function getThumbnail(Request $request): ?Screenshot
    {
        $path = $request->path();

        return Screenshot::with('timeInterval')
            ->where('thumbnail_path', $path)
            ->first();
    }
}
