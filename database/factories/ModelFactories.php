<?php

use App\User;
use Illuminate\Database\Eloquent\Factory;
use App\Models\Factories\UserFactory;


/** @var Factory $factory */
$factory->define(User::class, [app(UserFactory::class), 'getRandomUserData']);

