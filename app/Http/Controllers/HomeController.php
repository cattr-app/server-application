<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use App\Helpers\FileHelper;

/**
 * Class HomeController
 *
 * @package App\Http\Controllers
 */
class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $path = $request->path();
        if (preg_match('/js\/.*\.(js|css)/', $path)) {
            // If frontend requested for a non-existent style or script,
            // return the no-content response instead of the index.html.
            return response(null, 204);
        }

        $file_helper = new FileHelper();
        return view('welcome', [
            'styles' => $file_helper->getStyles(),
            'scripts' => $file_helper->getScripts(),
        ]);
    }
}
