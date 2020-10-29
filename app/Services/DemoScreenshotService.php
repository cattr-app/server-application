<?php

namespace App\Services;

use App\Models\Screenshot;
use App\Contracts\ScreenshotService as ScreenshotServiceInterface;
use Illuminate\Http\Request;

class DemoScreenshotService implements ScreenshotServiceInterface
{
    /**
     * Get screenshot by id.
     *
     * @param Request $request
     * @return Screenshot|null
     */
    public function getScreenshot(Request $request): ?Screenshot
    {
        return Screenshot::withoutGlobalScopes()->with('timeInterval')
            ->where('path', $request->screenshot)
            ->first();
    }

    /**
     * Get screenshot thumbnail by id.
     *
     * @param Request $request
     * @return Screenshot
     */
    public function getThumbnail(Request $request): ?Screenshot
    {
        return Screenshot::withoutGlobalScopes()->with('timeInterval')
            ->where('thumbnail_path', $request->screenshot)
            ->first();
    }
}
