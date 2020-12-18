<?php

namespace App\Observers;

use App\Mail\UserCreated;
use App\Models\User;
use App\Services\SettingsService;
use Mail;
use Illuminate\Support\Str;

class UserObserver
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Handle the user "creating" event.
     *
     * @param User $user
     * @return void
     */
    public function creating(User $user): void
    {
        if (!$user->password || request('send_invite')) {
            $password = request('password') ?? Str::random(16);

            $user->password = $password;
            $user->invitation_sent = true;

            $language = $this->settingsService->get('core', 'language', 'en');

            Mail::to($user->email)->locale($language)->send(new UserCreated($user->email, $password));
        }
    }
}
