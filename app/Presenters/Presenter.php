<?php

namespace App\Presenters;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Presenter
 * @package App\Presenters
 */
abstract class Presenter
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Presenter constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->model, $method], $args);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->model->{$name};
    }
}
