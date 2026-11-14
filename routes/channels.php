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

use App\Models\User;

Broadcast::channel('tasks.{userId}', static function (User $user, $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('projects.{userId}', static function (User $user, $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('gantt.{userId}', static function (User $user, $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('intervals.{userId}', static function (User $user, $userId) {
    return (int)$user->id === (int)$userId;
});
Broadcast::channel('tasks_activities.{userId}', static function (User $user, $userId) {
    return (int)$user->id === (int)$userId;
});

// Progress of the background processing of offline screenshot archives uploaded by the user
Broadcast::channel('offline-sync.{userId}', static function (User $user, $userId) {
    return (int)$user->id === (int)$userId;
});
