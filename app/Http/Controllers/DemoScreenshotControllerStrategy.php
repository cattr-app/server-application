<?php

namespace App\Http\Controllers;

use App\Models\Screenshot;
use Illuminate\Http\Request;

class DemoScreenshotControllerStrategy implements ScreenshotControllerStrategyInterface
{
    public function getScreenshot(Request $request): ?Screenshot
    {
        return Screenshot::with('timeInterval')
            ->where('id', $request->screenshot)
            ->first();
    }

    public function getThumbnail(Request $request): ?Screenshot
    {
        return Screenshot::with('timeInterval')
            ->where('id', $request->screenshot)
            ->first();
    }
}
