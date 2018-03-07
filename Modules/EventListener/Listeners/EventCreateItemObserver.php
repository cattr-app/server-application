<?php

namespace Modules\EventListener\Listeners;

class EventCreateItemObserver extends AbstractEventObserver
{
    public function __construct()
    {
        parent::__construct();
    }

    function getObserveredAction()
    {
        return 'create';
    }
}
