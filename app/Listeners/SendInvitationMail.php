<?php

namespace App\Listeners;

use App\Events\InvitationCreated;
use App\Mail\UserInvited as UserInvitedMail;
use Mail;
use Settings;

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

        $language = Settings::get('core', 'language', 'en');

        Mail::to($email)->locale($language)->send(new UserInvitedMail($key));
    }
}
