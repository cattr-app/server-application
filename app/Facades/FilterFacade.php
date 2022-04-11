<?php

namespace App\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Testing\Fakes\EventFake;

/**
 * @see Dispatcher
 * @method static subscribe(object|string $subscriber)
 * @method static listen(string|array $events, mixed $listener)
 * @method static process(string $event, mixed $payload)
 * @mixin Dispatcher
 */
class FilterFacade extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param array|string $eventsToFake
     * @return void
     */
    public static function fake(array|string $eventsToFake = []): void
    {
        static::swap($fake = new EventFake(static::getFacadeRoot(), $eventsToFake));

        Model::setEventDispatcher($fake);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'filter';
    }
}
