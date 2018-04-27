<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Task;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Filter;
use DateTime;



/**
 * Class TaskController
 *
 * @package App\Http\Controllers\Api\v1
 */
class TaskController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Task::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'project_id'  => 'required',
            'task_name'   => 'required',
            'active'      => 'required',
            'user_id'     => 'required',
            'assigned_by' => 'required',
            'url'         => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'task';
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $items = Task::where('user_id', '=', $user->id)->get();


        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }


    public function dashboard(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();


        $limit = request()->limit;




        $items = Task::where('user_id', '=', $user->id)
            ->whereHas('timeIntervals', function ($query) {

                    $YersterdayTimestamp = time() - 60 /* sec */ * 60  /* min */ * 24 /* hours */;
                    $compareDate = date("Y-m-d H:i:s", $YersterdayTimestamp );

                    $query->where('updated_at', '>=', $compareDate);
            })
            ->take(10)
            ->get();


        foreach ($items as $key => $task) {

            $totalTime = 0;

            foreach ($task->timeIntervals as $timeInterval) {

                $end = new DateTime($timeInterval->end_at);
                $totalTime += $end->diff(new DateTime($timeInterval->start_at))->s;
            }


            $items[$key]->total_time = gmdate("H:i:s", $totalTime);
        }


        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }

}
