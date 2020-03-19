<?php

namespace App\EventFilter;

use Closure;
use Illuminate\Events\Dispatcher as LaravelDispatcher;

class Dispatcher extends LaravelDispatcher
{
    /**
     * @param $event
     * @param mixed $payload
     * @return mixed
     */
    public function process($event, $payload)
    {
        return $this->dispatch($event, [$payload]);
    }

    /**
     * @param object|string $event
     * @param array $payload
     * @param bool $halt
     * @return mixed
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
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

        return $halt ? null : ($payload[0] ?? null);
    }


    /**
     * Register an event listener with the dispatcher.
     *
     * @param Closure|string $listener
     * @param bool $wildcard
     * @return Closure
     */
    public function makeListener($listener, $wildcard = false): callable
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener, $wildcard);
        }

        return static function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $listener($event, $payload[0]);
            }

            return $listener($payload[0]);
        };
    }

    /**
     * Create a class based listener using the IoC container.
     *
     * @param string $listener
     * @param bool $wildcard
     * @return Closure
     */
    public function createClassListener($listener, $wildcard = false): callable
    {
        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return call_user_func($this->createClassCallable($listener), $event, $payload[0]);
            }

            return call_user_func_array(
                $this->createClassCallable($listener),
                $payload
            );
        };
    }
}
