<?php

namespace App\Helpers;

use Closure;
use Illuminate\Events\Dispatcher as LaravelDispatcher;

class FilterDispatcher extends LaravelDispatcher
{
    public static function getRequestFilterName(): string
    {
        return 'filter.request.' . request()?->route()?->getName();
    }

    public static function getQueryFilterName(): string
    {
        return 'filter.query.get.' . request()?->route()?->getName();
    }

    public static function getQueryAdditionalRelationsFilterName(): string
    {
        return 'filter.query.with.' . request()?->route()?->getName();
    }

    public static function getSuccessResponseFilterName(): string
    {
        return 'filter.response.success.' . request()?->route()?->getName();
    }

    public static function getErrorResponseFilterName(): string
    {
        return 'filter.response.error.' . request()?->route()?->getName();
    }

    public static function getValidationFilterName(): string
    {
        return 'filter.validation.' . request()?->route()?->getName();
    }

    public static function getAuthFilterName(): string
    {
        return 'filter.authorize.' . request()?->route()?->getName();
    }

    public static function getAuthValidationFilterName(): string
    {
        return 'filter.authorize.validated' . request()?->route()?->getName();
    }

    public static function getBeforeActionEventName(): string
    {
        return 'event.before.action.' . request()?->route()?->getName();
    }

    public static function getAfterActionEventName(): string
    {
        return 'event.after.action.' . request()?->route()?->getName();
    }

    public static function getActionFilterName(): string
    {
        return 'filter.action.' . request()?->route()?->getName();
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
