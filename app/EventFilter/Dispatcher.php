<?php

namespace App\EventFilter;

use Illuminate\Events\Dispatcher as LaravelDispatcher;

class Dispatcher extends LaravelDispatcher
{
    /**
     * @param object|string $event
     * @param array $payload
     * @param bool $halt
     * @return mixed
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        [$event, $payload] = $this->parseEventAndPayload($event, $payload);

        if ($this->shouldBroadcast($payload)) {
            $this->broadcastEvent($payload[0]);
        }

        foreach ($this->getListeners($event) as $listener) {
            $response = $listener($event, $payload);

            if ($halt && null !== $response) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            $payload = $response;
        }

        return $halt ? null : $payload;
    }
}
