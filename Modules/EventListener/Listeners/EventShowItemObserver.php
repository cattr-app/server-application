<?php

namespace Modules\EventListener\Listeners;

class EventShowItemObserver extends AbstractEventObserver
{
    public function getObservedAction(): string
    {
        return 'show';
    }
}
