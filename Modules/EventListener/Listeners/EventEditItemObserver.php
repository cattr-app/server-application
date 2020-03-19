<?php

namespace Modules\EventListener\Listeners;

class EventEditItemObserver extends AbstractEventObserver
{

    public function getObservedAction(): string
    {
        return 'edit';
    }
}
