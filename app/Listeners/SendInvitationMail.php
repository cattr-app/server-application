<?php

namespace App\Listeners;

use App\Events\InvitationCreated;
use App\Mail\UserInvited as UserInvitedMail;
use Illuminate\Support\Facades\Mail;

class SendInvitationMail
{
    /**
     * Handle the given event.
     *
     * @param InvitationCreated $event
     */
    public function handle(InvitationCreated $event): void
    {
        $email = $event->invitation->email;
        $key = $event->invitation->key;

        Mail::to($email)->send(new UserInvitedMail($key));
    }
}
