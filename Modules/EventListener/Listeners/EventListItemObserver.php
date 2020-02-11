<?php

namespace Modules\EventListener\Listeners;

class EventListItemObserver extends AbstractEventObserver
{
    public function __construct()
    {
        parent::__construct();
    }

    function getObserveredAction()
    {
        return 'list';
    }
}
