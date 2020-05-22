<?php

namespace App\Observers;

use App\Mail\UserCreated as UserCreatedMail;
use App\Models\User;
use App\Events\UserCreated;
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
    public function creating(User $user): void
    {
        if (!$user->password && request()->input('send_invite')) {
            $password = Str::random(16);

            $user->password = $password;
            $user->invitation_sent = true;

            Mail::to($user->email)->send(new UserCreatedMail($user->email, $password));
        }
    }
}
