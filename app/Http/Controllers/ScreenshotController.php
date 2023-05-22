<?php

namespace App\Http\Controllers;

use App\Enums\ScreenshotsState;
use Illuminate\Routing\Controller;


class ScreenshotController extends Controller
{
    public function getScreenshotStates() 
    {
        $items = (array_map(fn($case) => $case->toArray(),ScreenshotsState::cases()));

        return responder()->success($items)->respond();
    }
}
