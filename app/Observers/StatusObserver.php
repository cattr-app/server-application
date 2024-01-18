<?php

namespace App\Observers;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use App\Models\CronTaskWorkers;
use App\Models\Status;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;

class StatusObserver
{
    public function statusEdition($item, $requestData)
    {
        $prevItem = Status::where('order', '=', $requestData['order'])->first();
        if (isset($prevItem)) {
            $prevItem->order = $item->order;
            $prevItem->save();
        }
    }

    public function statusCreation($item)
    {
        $order = Status::find($item['id']);
        $maxOrder = Status::max('order');
        $order->order = $maxOrder + 1;
        $order->save();
    }

    public function subscribe(): array
    {
        return [
            'event.before.action.statuses.edit' => [[__CLASS__, 'statusEdition']],
            'event.after.action.statuses.create' => [[__CLASS__, 'statusCreation']],
        ];
    }
}
