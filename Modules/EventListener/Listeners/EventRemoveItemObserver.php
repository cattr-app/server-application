<?php

namespace Modules\EventListener\Listeners;

class EventRemoveItemObserver extends AbstractEventObserver
{
    public function getObservedAction(): string
    {
        return 'remove';
    }
}
