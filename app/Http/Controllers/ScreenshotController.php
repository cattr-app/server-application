<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScreenshotController extends Controller
{
    /** @var ScreenshotControllerStrategyInterface */
    protected ScreenshotControllerStrategyInterface $strategy;

    /**
     * @param ScreenshotControllerStrategyInterface $strategy
     *
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(ScreenshotControllerStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response|BinaryFileResponse
     */
    public function screenshot(Request $request)
    {
        $screenshot = $this->strategy->getScreenshot($request);
        if (!isset($screenshot)) {
            return response(null, 404);
        }

        $user = $request->user();
        if (!$screenshot->access($user)) {
            return response(null, 403);
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
        $screenshot = $this->strategy->getThumbnail($request);
        if (!isset($screenshot)) {
            return response(null, 404);
        }

        $user = $request->user();
        if (!$screenshot->access($user)) {
            return response(null, 403);
        }

        $full_path = storage_path('app/' . $screenshot->thumbnail_path);
        if (!file_exists($full_path)) {
            return response(null, 404);
        }

        return response()->file($full_path);
    }
}
