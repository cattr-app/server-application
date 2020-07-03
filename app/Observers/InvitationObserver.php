<?php

namespace App\Observers;

use App\Events\InvitationCreated;
use App\Models\Invitation;

class InvitationObserver
{
    /**
     * Handle the invitation "created" event.
     *
     * @param Invitation $invitation
     * @return void
     */
    public function created(Invitation $invitation): void
    {
        event(new InvitationCreated($invitation));
    }

    /**
     * Handle the invitation "updated" event.
     *
     * @param Invitation $invitation
     * @return void
     */
    public function updated(Invitation $invitation): void
    {
        event(new InvitationCreated($invitation));
    }
}
