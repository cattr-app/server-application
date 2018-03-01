<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Project;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
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

        return response()->json(
            $projects, 200);
    }

    /**
     * Show the form for creating a new resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $requestData = $request->all();

        $project = Project::create($requestData);

        return response()->json([
            'res' => $project,
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
        $projectId = $request->get('project_id');
        $project = Project::findOrFail($projectId);

        return response()->json($project, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $projectId = $request->get('project_id');
        $project = Project::findOrFail($projectId);

        $project->company_id = $request->get('company_id');
        $project->name = $request->get('name');
        $project->description = $request->get('description');

        $project->save();

        return response()->json([
            'project' => $project,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    public function destroy(Request $request)
    {
        $projectId = $request->get('project_id');

        $project = Project::findOrFail($projectId);
        $project->delete();

        return response()->json(['message'=>'project has been removed']);
    }
}
