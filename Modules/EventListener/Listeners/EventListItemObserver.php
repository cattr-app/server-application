<?php

namespace Modules\EventListener\Listeners;

class EventListItemObserver extends AbstractEventObserver
{
    public function getObservedAction(): string
    {
        return 'list';
    }
}
