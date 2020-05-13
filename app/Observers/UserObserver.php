<?php

namespace App\Observers;

use App\Mail\InviteUser;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the user "creating" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function creating(User $user)
    {
        if (!$user->password && request()->input('send_invite')) {
            $password = Str::random(16);

            $user->password = $password;
            $user->invitation_sent = true;

            Mail::to($user->email)->send(new InviteUser($user->email, $password));
        }
    }
}
