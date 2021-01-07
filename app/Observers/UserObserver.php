<?php

namespace App\Observers;

use App\Mail\UserCreated;
use App\Models\User;
use Mail;
use Illuminate\Support\Str;
use Settings;

class UserObserver
{
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

            $language = Settings::get('core', 'language', 'en');

            Mail::to($user->email)->locale($language)->send(new UserCreated($user->email, $password));
        }
    }
}
