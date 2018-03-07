<?php

namespace Modules\EventListener\Listeners;

class EventRemoveItemObserver extends AbstractEventObserver
{
    public function __construct()
    {
        parent::__construct();
    }

    function getObserveredAction()
    {
        return 'remove';
    }
}
