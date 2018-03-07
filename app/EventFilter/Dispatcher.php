<?php

namespace App\EventFilter;

use Illuminate\Events\Dispatcher as LaravelDispatcher;

class Dispatcher extends LaravelDispatcher
{
    /**
     * @param $event
     * @param array ...$payload
     * @return mixed
     */
    public function process($event, ...$payload)
    {
        $data = $this->dispatch($event, $payload);

        return \is_array($data) ? ($data[0] ?? null) : $data;
    }

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

            $payload[0] = $response;
        }

        return $halt ? null : $payload;
    }


    /**
     * Register an event listener with the dispatcher.
     *
     * @param  \Closure|string  $listener
     * @param  bool  $wildcard
     * @return \Closure
     */
    public function makeListener($listener, $wildcard = false): callable
    {
        if (\is_string($listener)) {
            return $this->createClassListener($listener, $wildcard);
        }

        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $listener($event, ...$payload);
            }

            return $listener(...array_values($payload));
        };
    }
}
