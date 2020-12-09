<?php

namespace App\Services;

use App\Models\Screenshot;
use App\Contracts\ScreenshotService as ScreenshotServiceInterface;
use Illuminate\Http\Request;

class ScreenshotService implements ScreenshotServiceInterface
{
    /**
     * Get screenshot by request path.
     *
     * @param Request $request
     * @return Screenshot
     */
    public function getScreenshot(Request $request): ?Screenshot
    {
        return Screenshot::with('timeInterval')
            ->where('path', $request->path())
            ->first();
    }

    /**
     * Get screenshot thumbnail by request path.
     *
     * @param Request $request
     * @return Screenshot
     */
    public function getThumbnail(Request $request): ?Screenshot
    {
        return Screenshot::with('timeInterval')
            ->where('thumbnail_path', $request->path())
            ->first();
    }
}
