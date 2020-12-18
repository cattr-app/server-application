<?php

namespace App\Listeners;

use App\Events\InvitationCreated;
use App\Mail\UserInvited as UserInvitedMail;
use App\Services\SettingsService;
use Mail;

class SendInvitationMail
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Handle the given event.
     *
     * @param InvitationCreated $event
     */
    public function handle(InvitationCreated $event): void
    {
        $email = $event->invitation->email;
        $key = $event->invitation->key;

        $language = $this->settingsService->get('core', 'language', 'en');

        Mail::to($email)->locale($language)->send(new UserInvitedMail($key));
    }
}
