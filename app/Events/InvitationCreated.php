<?php

namespace App\Events;

use App\Models\Invitation;

class InvitationCreated
{
    public function __construct(public Invitation $invitation)
    {
    }
}
