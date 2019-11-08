<?php

use App\User;
use Illuminate\Database\Eloquent\Factory;
use Facades\App\Models\Factories\UserFactory;


/** @var Factory $factory */
$factory->define(User::class, [UserFactory::class, 'getRandomUserData']);

