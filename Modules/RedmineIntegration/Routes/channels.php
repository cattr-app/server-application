<?php

Broadcast::channel('task.updates.{userId}', static function ($user, $userId) {
    return (int)$user->id === (int)$userId;
});
