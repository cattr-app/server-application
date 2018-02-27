<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Task;
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

        return response()->json([
            'tasks' => $tasks,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('tasks.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        
        $requestData = $request->all();
        
        $task = Task::create($requestData);

        return response()->json([
            'task'    => $task,
            'message' => 'Success'
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $task = Task::findOrFail($id);

        return response()->json([
            'task'    => $task,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $task = Task::findOrFail($id);

        return response()->json([
            'task'    => $task,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->all();
        
        $task = Task::findOrFail($id);
        $task->update($requestData);

        return response()->json([
            'task'    => $task,
            'message' => 'Success'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return string
     */
    public function destroy($id)
    {
        Task::destroy($id);

        return "Task successfully deleted ";
    }
}
