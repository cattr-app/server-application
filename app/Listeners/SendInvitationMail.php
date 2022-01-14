<?php

namespace App\Listeners;

use App\Events\InvitationCreated;
use App\Mail\UserInvited as UserInvitedMail;
use Mail;
use Settings;

class SendInvitationMail
{
    public function handle(InvitationCreated $event): void
    {
        $email = $event->invitation->email;
        $key = $event->invitation->key;

        $language = Settings::scope('core')->get('language', 'en');

        Mail::to($email)->locale($language)->send(new UserInvitedMail($key));
    }
}
