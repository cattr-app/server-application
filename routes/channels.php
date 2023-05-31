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

Broadcast::channel('Tasks', static function () {
    return Auth::check();
});

Broadcast::channel('TasksDeleted', static function () {
    return Auth::check();
});

Broadcast::channel('ProjectsDeleted', static function () {
    return Auth::check();
});