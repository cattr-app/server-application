<?php

namespace Modules\EventListener\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Log;

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

    public function request($event, $data)
    {
        Log::info('Request to ' . $this->getObserveredAction() . ' item', ['data' => $data]);

        return $data;
    }

    public function validate($event, $validationRules)
    {
        Log::info('Validation when ' . $this->getObserveredAction() . ' item', ['validationRules' => $validationRules]);

        return $validationRules;
    }

    public function answerError($event, $errorMessages)
    {
        Log::info('Error when ' . $this->getObserveredAction() . ' item', ['errors' => $errorMessages]);

        return $errorMessages;
    }

    public function action($event, $item)
    {
        Log::info($this->getObserveredAction() . ' item action', ['item' => $item]);

        return $item;
    }

    public function answerSuccess($event, $successMessages)
    {
        Log::info("Successful " . $this->getObserveredAction() . ' item', ['successMessage' => $successMessages]);

        return $successMessages;
    }

}
