<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $keyword = $request->get('search');
        $perPage = 25;

        if (!empty($keyword)) {
            $users = User::paginate($perPage);
        } else {
            $users = User::paginate($perPage);
        }

        return response()->json(
            $users, 200);
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

        $user = User::create($requestData);

        return response()->json([
            'res' => $user,
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
        $userId = $request->get('user_id');
        $user = User::findOrFail($userId);

        return response()->json($user, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $userId = $request->get('user_id');
        $user = User::findOrFail($userId);

        $user->full_name = $request->get('full_name');
        $user->first_name = $request->get('first_name');
        $user->last_name = $request->get('last_name');
        $user->email = $request->get('email');
        $user->url = $request->get('url');
        $user->company_id = $request->get('company_id');
        $user->level = $request->get('level');
        $user->payroll_access = $request->get('payroll_access');
        $user->billing_access = $request->get('billing_access');
        $user->avatar = $request->get('avatar');
        $user->screenshots_active = $request->get('screenshots_active');
        $user->manual_time = $request->get('manual_time');
        $user->permanent_tasks = $request->get('permanent_tasks');
        $user->computer_time_popup = $request->get('computer_time_popup');
        $user->poor_time_popup = $request->get('poor_time_popup');
        $user->blur_screenshots = $request->get('blur_screenshots');
        $user->web_and_app_monitoring = $request->get('web_and_app_monitoring');
        $user->webcam_shots = $request->get('webcam_shots');
        $user->screenshots_interval = $request->get('screenshots_interval');
        $user->user_role_value = $request->get('user_role_value');
        $user->active = $request->get('active');
        $user->password = $request->get('password');

        $user->save();

        return response()->json([
            'taks' => $user,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function destroy(Request $request)
    {
        $userId = $request->get('user_id');

        $user = User::findOrFail($userId);
        $user->delete();

        return response()->json(['message'=>'task has been removed']);
    }
}

