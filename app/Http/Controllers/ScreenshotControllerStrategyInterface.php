<?php

namespace App\Http\Controllers;

use App\Models\Screenshot;
use Illuminate\Http\Request;

interface ScreenshotControllerStrategyInterface
{
    public function getScreenshot(Request $request): ?Screenshot;
    public function getThumbnail(Request $request): ?Screenshot;
}
