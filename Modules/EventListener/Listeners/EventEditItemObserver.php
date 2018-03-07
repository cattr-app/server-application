<?php

namespace Modules\EventListener\Listeners;

class EventEditItemObserver extends AbstractEventObserver
{
    public function __construct()
    {
        parent::__construct();
    }

    function getObserveredAction()
    {
        return 'edit';
    }
}
