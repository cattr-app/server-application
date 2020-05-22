<?php

namespace App\Observers;

use App\Events\InvitationCreated;
use App\Models\Invitation;

class InvitationObserver
{
    /**
     * Handle the invitation "created" event.
     *
     * @param  \App\Models\Invitation  $invitation
     * @return void
     */
    public function created(Invitation $invitation)
    {
        event(new InvitationCreated($invitation));
    }

    /**
     * Handle the invitation "updated" event.
     *
     * @param  \App\Models\Invitation  $invitation
     * @return void
     */
    public function updated(Invitation $invitation)
    {
        event(new InvitationCreated($invitation));
    }
}
