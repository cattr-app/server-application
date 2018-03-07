<?php

namespace Modules\EventListener\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class EventObserver
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function createItem($answer): array
    {
        $answer['additional_info'] = 'module';
        dd($answer);

        return $answer;
    }

    /*public function handle($event, $answer)
    {
        $answer['additional_info'] = 'module';
        dd("OPA");

        return $answer;
    }*/
}
