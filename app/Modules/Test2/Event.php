<?php

namespace App\Modules\Test2;


use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class Event
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array $answer
     * @return array
     */
    public function modifyAnswer(array $answer): array
    {
        $answer['additional_info_2'] = 'Some other string';

        return $answer;
    }
}
