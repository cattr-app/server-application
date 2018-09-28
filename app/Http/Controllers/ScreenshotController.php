<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Screenshot;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\User;

/**
 * Class ScreenshotController
 *
 * @package App\Http\Controllers
 */
class ScreenshotController extends Controller
{
    public function screenshot(Request $request)
    {
        $path = $request->path();
        $screenshot = Screenshot::with('timeInterval')
            ->where('path', $path)
            ->firstOrFail();

        $screenshot_full_path = storage_path('app/' . $screenshot->path);
        $screenshot_user_id = $screenshot->timeInterval->user_id;

        $user = $request->user();

        // Allow root to see all screenshots.
        if (Role::can($user, 'screenshots', 'full_access')) {
            return response()->file($screenshot_full_path);
        }

        // Allow user to see own screenshots.
        if (Role::can($user, 'screenshots', 'list') && $user->id === $screenshot_user_id) {
            return response()->file($screenshot_full_path);
        }

        // Allow manager to see screenshots of related users.
        if (Role::can($user, 'screenshots', 'manager_access')) {
            if (Role::can($user, 'projects', 'relations')) {
                $attached_project_ids = $user->projects->pluck('id');
                $related_user_ids = User::whereHas('timeIntervals', function ($query) use ($attached_project_ids) {
                    $query->whereHas('task', function ($query) use ($attached_project_ids) {
                        $query->whereHas('project', function ($query) use ($attached_project_ids) {
                            $query->whereIn('id', $attached_project_ids);
                        });
                    });
                })->select('id')->get('id')->pluck('id');
                if ($related_user_ids->contains($screenshot_user_id)) {
                    return response()->file($screenshot_full_path);
                }
            }

            if (Role::can($user, 'projects', 'relations')) {
                $attached_user_ids = $user->attached_users->pluck('id');
                if ($attached_user_ids->contains($screenshot_user_id)) {
                    return response()->file($screenshot_full_path);
                }
            }
        }

        return response(null, 403);
    }
}
