<?php

namespace App\Http\Controllers;

use App\Models\Screenshot;
use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


/**
 * Class ScreenshotController
 *
 * @package App\Http\Controllers
 */
class ScreenshotController extends Controller
{
    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
    }

    /**
     * @param Request $request
     * @return ResponseFactory|Response|BinaryFileResponse
     */
    public function screenshot(Request $request)
    {
        $path = $request->path();
        $screenshot = Screenshot::with('timeInterval')
            ->where('path', $path)
            ->first();
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

    public function thumbnail(Request $request)
    {
        $path = $request->path();
        $screenshot = Screenshot::with('timeInterval')
            ->where('thumbnail_path', $path)
            ->first();
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
