<?php

namespace App\Presenters\User;

use App\Models\User;

/**
 * Class UserPresenter
 * @package App\Presenters\User
 */
class UserPresenter
{
    /**
     * @var User
     */
    protected $model;

    /**
     * UserPresenter constructor.
     * @param User $model
     */
    public function __construct(User $model)
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
