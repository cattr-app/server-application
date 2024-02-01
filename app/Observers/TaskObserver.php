<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    public static function  taskCreation($item)
    {
        $maxPosition = Task::max('relative_position');
        $item['relative_position'] = $maxPosition + 1;
        return $item;
    }
    public function subscribe(): array
    {
        return [];
    }
}
