<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Project;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $keyword = $request->get('search');
        $perPage = 25;

        if (!empty($keyword)) {
            $projects = Project::paginate($perPage);
        } else {
            $projects = Project::paginate($perPage);
        }

        return response()->json([
            'projects' => $projects,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     *
     * @return string
     */
    public function store(Request $request)
    {
        $requestData = $request->all();
        
        $project = Project::create($requestData);

        return response()->json([
            'project'    => $project,
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
        $project = Project::findOrFail($id);

       // return view('projects.show', compact('project'));

        return response()->json([
            'project'    => $project,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $project = Project::findOrFail($id);

        return response()->json([
            'project'    => $project,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int $id

     * @return string
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->all();
        
        $project = Project::findOrFail($id);
        $project->update($requestData);

        return response()->json([
            'project'    => $project,
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
        Project::destroy($id);

        return "Project successfully deleted ";
    }
}
