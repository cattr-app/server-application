<?php

namespace Modules\EventListener\Listeners;

class EventShowItemObserver extends AbstractEventObserver
{
    public function __construct()
    {
        parent::__construct();
    }

    function getObserveredAction()
    {
        return 'show';
    }
}
