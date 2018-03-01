<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $keyword = $request->get('search');
        $perPage = 25;

        if (!empty($keyword)) {
            $tasks = Task::paginate($perPage);
        } else {
            $tasks = Task::paginate($perPage);
        }

        return response()->json(
            $tasks, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $requestData = $request->all();

        $task = Task::create($requestData);

        return response()->json([
            'res' => $task,
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $taskId = $request->get('task_id');
        $task = Task::findOrFail($taskId);

        return response()->json($task, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $taskId = $request->get('task_id');
        $task = Task::findOrFail($taskId);

        $task->project_id = $request->get('project_id');
        $task->task_name = $request->get('task_name');
        $task->user_id = $request->get('user_id');
        $task->assigned_by = $request->get('assigned_by');

        $task->save();

        return response()->json([
            'taks' => $task,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request)
    {
        $taskId = $request->get('task_id');

        $task = Task::findOrFail($taskId);
        $task->delete();

        return response()->json(['message'=>'task has been removed']);
    }
}
