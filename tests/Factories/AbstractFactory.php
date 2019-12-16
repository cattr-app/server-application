<?php

namespace Tests\Factories;

use Illuminate\Support\Collection;

/**
 * Class AbstractFactory
 * @package Tests\Factories
 */
abstract class AbstractFactory
{
    /**
     * @param array $attributes
     * @return mixed
     */
    abstract public function create(array $attributes = []);

    /**
     * @param int $amount
     * @return Collection
     */
    public function createMany(int $amount = 1)
    {
        $collection = collect();

        while ($amount--) {
            $collection->push($this->create());
        }

        return $collection;
    }
}
