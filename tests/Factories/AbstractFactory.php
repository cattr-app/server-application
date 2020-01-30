<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;


/**
 * Class AbstractFactory
 */
abstract class AbstractFactory
{

    /**
     * @var bool
     */
    protected $timestampsHidden = true;

    /**
     * @param array $attributes
     * @return mixed
     */
    abstract public function create(array $attributes = []);

    protected function hideTimestamps(Model $model): Model
    {
        $model->makeHidden(['created_at', 'updated_at', 'deleted_at']);

        return $model;
    }

    /**
     * @param int $amount
     * @return Collection
     */
    public function createMany(int $amount = 1): Collection
    {
        $collection = collect();

        do {
            $collection->push($this->create());
        } while (--$amount);

        return $collection;
    }
}
