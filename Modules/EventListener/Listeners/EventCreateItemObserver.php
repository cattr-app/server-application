<?php

namespace Modules\EventListener\Listeners;

class EventCreateItemObserver extends AbstractEventObserver
{
    public function getObservedAction(): string
    {
        return 'create';
    }
}
