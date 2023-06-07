<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use Illuminate\Support\Facades\Auth;

Broadcast::channel('App.User.{id}', static function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('App.Models.Task.{id}', static function ($user, $id) {
    return Auth::check();
});

Broadcast::channel('App.Models.Project.{id}', static function ($user, $id) {
    return Auth::check();
});

Broadcast::channel('TaskCreated.{id}', static function () {
    return Auth::check();
});

Broadcast::channel('TaskUpdated.{id}', static function () {
    return Auth::check();
});

Broadcast::channel('TaskDeleted.{id}', static function () {
    return Auth::check();
});

Broadcast::channel('ProjectCreated.{id}', static function () {
    return Auth::check();
});

Broadcast::channel('ProjectUpdated.{id}', static function () {
    return Auth::check();
});

Broadcast::channel('ProjectDeleted.{id}', static function () {
    return Auth::check();
});


Broadcast::channel('TimeIntervalCreated.{id}', static function () {
    return Auth::check();
});

Broadcast::channel('TimeIntervalUpdated.{id}', static function () {
    return Auth::check();
});

Broadcast::channel('TimeIntervalDeleted.{id}', static function () {
    return Auth::check();
});