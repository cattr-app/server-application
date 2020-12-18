<?php

namespace App\Http\Controllers;

use App\Contracts\ScreenshotService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScreenshotController extends Controller
{
    /** @var ScreenshotService $screenshotService */
    protected ScreenshotService $screenshotService;

    /**
     * ScreenshotController constructor.
     * @param ScreenshotService $screenshotService
     */
    public function __construct(ScreenshotService $screenshotService)
    {
        $this->screenshotService = $screenshotService;
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response|BinaryFileResponse
     */
    public function screenshot(Request $request)
    {
        $screenshot = $this->screenshotService->getScreenshot($request);
        if (!isset($screenshot)) {
            return response(null, 404);
        }

        $full_path = storage_path('app/' . $screenshot->path);
        if (!file_exists($full_path)) {
            return response(null, 404);
        }

        return response()->file($full_path);
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response|BinaryFileResponse
     */
    public function thumbnail(Request $request)
    {
        $screenshot = $this->screenshotService->getThumbnail($request);
        if (!isset($screenshot)) {
            return response(null, 404);
        }

        $full_path = storage_path('app/' . $screenshot->thumbnail_path);
        if (!file_exists($full_path)) {
            return response(null, 404);
        }

        return response()->file($full_path);
    }
}
