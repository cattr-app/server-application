<?php

namespace App\Http\Controllers;

use App\Enums\ScreenshotsState;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Settings;

/**
 * Class RegistrationController
 * @codeCoverageIgnore until it is implemented on frontend
 */
class RegistrationController extends Controller
{
    /**
     * Get Form
     *
     * Returns invitation form data by a invitation token
     */
    public function getForm($key): JsonResponse
    {
        $invitation = Invitation::where('key', $key)
            ->where('expires_at', '>=', now())
            ->first();

        if (!isset($invitation)) {
            return new JsonResponse([
                'message' => __('The specified key has expired or does not exist')
            ], 404);
        }

        return responder()->success(['email' => $invitation->email])->respond();
    }

    /**
     * Creates a new user.
     *
     * @param RegistrationRequest $request
     * @param string $key
     * @return JsonResponse
     */
    public function postForm(RegistrationRequest $request, string $key): JsonResponse
    {
        $invitation = Invitation::where('key', $key)
            ->where('expires_at', '>=', now())
            ->first();

        if (!isset($invitation)) {
            return new JsonResponse([
                'message' => __('The specified key has expired or does not exist'),
            ], 404);
        }

        if ($request->input('email') !== $invitation->email) {
            return new JsonResponse([
                'message' => __('The email address does not match the key'),
            ], 400);
        }

        $language = Settings::scope('core')->get('language', 'en');

        /** @var User $user */
        $user = User::create([
            'full_name' => $request->input('full_name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'active' => true,
            'manual_time' => false,
            'screenshots_state' => ScreenshotsState::REQUIRED,
            'screenshots_state_locked' => true,
            'computer_time_popup' => 3,
            'screenshots_interval' => 10,
            'role_id' => $invitation->role_id,
            'user_language' => $language,
        ]);

        $invitation->delete();

        return responder()->success(['user_id' => $user->id])->respond();
    }
}
