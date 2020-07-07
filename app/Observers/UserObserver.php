<?php

namespace App\Observers;

use App\Mail\UserCreated;
use App\Models\User;
use Mail;
use Illuminate\Support\Str;

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
        if (!$user->password && request()->input('send_invite')) {
            $password = Str::random(16);

            $user->password = $password;
            $user->invitation_sent = true;

            Mail::to($user->email)->send(new UserCreated($user->email, $password));
        }
    }
}
