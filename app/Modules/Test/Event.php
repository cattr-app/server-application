<?php

namespace App\Modules\Test;


use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class Event
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $event
     * @param array $answer
     * @return array
     */
    public function modifyAnswer(string $event, array $answer): array
    {
        $answer['additional_info'] = 'Some string';

        return $answer;
    }
}
