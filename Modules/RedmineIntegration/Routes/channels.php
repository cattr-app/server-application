<?php

Broadcast::channel("task.updates.{userId}", function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
