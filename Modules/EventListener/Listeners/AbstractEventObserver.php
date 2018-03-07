<?php

namespace Modules\EventListener\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AbstractEventObserver
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract function getObserveredAction();

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    //Create items events
    public function request($event, $data)
    {
        //TODO: Save to log

        return $data;
    }

    public function validate($event, $validationRules)
    {
        //TODO: Save to log

        return $validationRules;
    }

    public function answerError($event, $errorMessages)
    {
        //TODO: Save to log

        return $errorMessages;
    }

    public function action($event, $item)
    {
        //TODO: Save to log

        return $item;
    }

    public function answerSuccess($event, $successMessages)
    {
        //TODO: Save to log

        return $successMessages;
    }

}
