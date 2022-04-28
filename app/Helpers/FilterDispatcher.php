<?php

namespace App\Helpers;

use Closure;
use Illuminate\Events\Dispatcher as LaravelDispatcher;

class FilterDispatcher extends LaravelDispatcher
{
    public static function getQueryPrepareFilterName(): string
    {
        return 'query.prepare.' . request()?->route()?->getName();
    }

    public static function getQueryGetFilterName(): string
    {
        return 'query.get.' . request()?->route()?->getName();
    }

    public static function getQueryFiltrationFilterName(): string
    {
        return 'query.filter.' . request()?->route()?->getName();
    }

    public static function getSuccessResponseFilterName(): string
    {
        return 'response.success.' . request()?->route()?->getName();
    }

    public static function getErrorResponseFilterName(): string
    {
        return 'response.error.' . request()?->route()?->getName();
    }

    public static function getValidationFilterName(): string
    {
        return 'validation.' . request()?->route()?->getName();
    }

    public static function getAuthFilterName(): string
    {
        return 'authorize.' . request()?->route()?->getName();
    }

    public static function getAuthValidationFilterName(): string
    {
        return 'authorize.validated' . request()?->route()?->getName();
    }

    /**
     * @inerhitDoc
     * @param $event
     * @param mixed $payload
     * @return mixed
     */
    public function process($event, mixed $payload): mixed
    {
        return $this->dispatch($event, [$payload]);
    }

    /**
     * @inerhitDoc
     * @param object|string $event
     * @param array $payload
     * @param bool $halt
     * @return mixed
     */
    public function dispatch($event, mixed $payload = [], $halt = false): mixed
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
