<?php

namespace App\Contracts;

use App\Models\Screenshot;
use Illuminate\Http\Request;

interface ScreenshotService
{
    public function getScreenshot(Request $request): ?Screenshot;
    public function getThumbnail(Request $request): ?Screenshot;
}
