<?php

namespace App\Observers;

use App\Models\Status;

class StatusObserver
{
    public function statusEdition($item, $requestData)
    {
        $nextItem = Status::where('order', '=', $requestData['order'])->first();
        $itemOrder = $nextItem->order;
        if (isset($nextItem)) {
            Status::where('order', '=',  $item->order)->update(['order' => 0]);
            $nextItem->order = $item->order;
            $item->order = $itemOrder;
            $nextItem->save();
            Status::where('order', '=', 0)->update(['order' => $requestData['order']]);
        }
    }

    public static function  statusCreation($item)
    {
        $maxOrder = Status::max('order');
        $item['order'] = $maxOrder + 1;
        return $item;
    }

    public function subscribe(): array
    {
        return [
            'event.before.action.statuses.edit' => [[__CLASS__, 'statusEdition']],
        ];
    }
}
