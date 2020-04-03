<?php

namespace Modules\EventListener\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Log;

abstract class AbstractEventObserver
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function request($event, $data)
    {
        Log::info('Request to ' . $this->getObservedAction() . ' item', ['data' => $data]);

        return $data;
    }

    abstract public function getObservedAction();

    public function validate($event, $validationRules)
    {
        Log::info('Validation when ' . $this->getObservedAction() . ' item', ['validationRules' => $validationRules]);

        return $validationRules;
    }

    public function answerError($event, $errorMessages)
    {
        Log::info('Error when ' . $this->getObservedAction() . ' item', ['errors' => $errorMessages]);

        return $errorMessages;
    }

    public function action($event, $item)
    {
        Log::info($this->getObservedAction() . ' item action', ['item' => $item]);

        return $item;
    }

    public function answerSuccess($event, $successMessages)
    {
        Log::info('Successful ' . $this->getObservedAction() . ' item', ['successMessage' => $successMessages]);

        return $successMessages;
    }
}
